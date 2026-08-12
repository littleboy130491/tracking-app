<?php

namespace Tests\Feature;

use App\Mail\CustomerOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_customer_can_request_and_use_displayed_otp(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'customer@example.com',
        ]);

        $this->post(route('customer.otp.request'), [
            'email' => $customer->email,
        ])->assertRedirectToRoute('customer.otp.show');

        $otp = session('customer_otp_demo_code');

        $this->get(route('customer.otp.show'))
            ->assertOk()
            ->assertSee($otp);

        $this->post(route('customer.otp.verify'), [
            'otp' => $otp,
        ])->assertRedirectToRoute('customer.dashboard');

        $this->assertAuthenticatedAs($customer);
        $this->assertNotNull($customer->fresh()->last_login_at);
    }

    public function test_otp_email_is_sent_when_enabled_and_not_shown_on_page(): void
    {
        config([
            'customer_auth.display_otp' => false,
            'customer_auth.send_email' => true,
        ]);

        Mail::fake();

        $customer = User::factory()->customer()->create([
            'email' => 'customer@example.com',
        ]);

        $this->post(route('customer.otp.request'), [
            'email' => $customer->email,
        ])->assertRedirectToRoute('customer.otp.show');

        $this->assertNull(session('customer_otp_demo_code'));
        $this->assertNotNull(session('customer_otp.hash'));

        Mail::assertSent(CustomerOtpMail::class, function (CustomerOtpMail $mail) use ($customer) {
            return $mail->hasTo($customer->email)
                && $mail->envelope()->subject === 'Kode verifikasi BL Tracking Anda'
                && preg_match('/^\d{6}$/', $mail->otp) === 1;
        });

        $this->get(route('customer.otp.show'))
            ->assertOk()
            ->assertSee('Kami telah mengirim kode 6 digit ke '.$customer->email)
            ->assertDontSee('Kode verifikasi demo');
    }

    public function test_wrong_otp_is_rejected(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'customer@example.com',
        ]);

        $this->post(route('customer.otp.request'), [
            'email' => $customer->email,
        ]);

        $this->post(route('customer.otp.verify'), [
            'otp' => '000000',
        ])
            ->assertSessionHasErrors([
                'otp' => 'OTP salah. Periksa kode dan coba lagi.',
            ]);

        $this->assertGuest();
    }

    public function test_unregistered_email_is_rejected(): void
    {
        $this->post(route('customer.otp.request'), [
            'email' => 'missing@example.com',
        ])->assertSessionHasErrors([
            'email' => 'Kami tidak dapat mengirim kode ke alamat ini. Hubungi administrator Anda.',
        ]);
    }

    public function test_admin_email_cannot_use_customer_otp_login(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->post(route('customer.otp.request'), [
            'email' => $admin->email,
        ])->assertSessionHasErrors([
            'email' => 'Kami tidak dapat mengirim kode ke alamat ini. Hubungi administrator Anda.',
        ]);
    }

    public function test_otp_is_invalidated_after_five_wrong_attempts(): void
    {
        $customer = User::factory()->customer()->create(['email' => 'customer@example.com']);

        $this->post(route('customer.otp.request'), ['email' => $customer->email]);

        foreach (range(1, 4) as $attempt) {
            $this->post(route('customer.otp.verify'), ['otp' => '000000'])
                ->assertSessionHasErrors('otp');
        }

        $this->post(route('customer.otp.verify'), ['otp' => '000000'])
            ->assertRedirectToRoute('customer.login')
            ->assertSessionHasErrors('email');

        $this->assertNull(session('customer_otp'));
    }

    public function test_inactive_customer_cannot_request_otp(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->post(route('customer.otp.request'), ['email' => $customer->email])
            ->assertSessionHasErrors('email');
    }
}
