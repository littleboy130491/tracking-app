<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_customer(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'email' => 'new-customer@example.com',
                'company_name' => 'New Customer Co',
                'company_address' => '123 Demo Street',
                'pic_name' => 'Jane Doe',
                'pic_phone' => '+62 812 0000 1111',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = User::query()->where('email', 'new-customer@example.com')->first();

        $this->assertNotNull($customer);
        $this->assertSame('New Customer Co', $customer->company_name);
        $this->assertSame('New Customer Co', $customer->name);
        $this->assertSame('123 Demo Street', $customer->company_address);
        $this->assertSame('Jane Doe', $customer->pic_name);
        $this->assertTrue($customer->hasRole(User::ROLE_CUSTOMER));
        $this->assertNotNull($customer->password);
    }

    public function test_admin_can_edit_a_customer_profile_and_email(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create([
            'company_name' => 'Old Company',
            'name' => 'Old Company',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, [
            'record' => $customer->getKey(),
        ])
            ->fillForm([
                'email' => 'updated@example.com',
                'company_name' => 'Updated Company',
                'pic_name' => 'Updated PIC',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();

        $this->assertSame('Updated Company', $customer->company_name);
        $this->assertSame('Updated Company', $customer->name);
        $this->assertSame('updated@example.com', $customer->email);
        $this->assertSame('Updated PIC', $customer->pic_name);
    }

    public function test_duplicate_customer_email_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->customer()->create([
            'email' => 'taken@example.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'email' => 'taken@example.com',
                'company_name' => 'Duplicate Customer',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_customer_records_are_visible_in_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create([
            'company_name' => 'Visible Customer',
            'email' => 'visible@example.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$customer]);
    }
}
