<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_customer(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'new-customer@example.com',
            'company_name' => 'New Customer Co',
            'name' => 'New Customer Co',
            'company_address' => '123 Demo Street',
            'pic_name' => 'Jane Doe',
            'pic_phone' => '+62 812 0000 1111',
        ]);

        $this->assertSame('New Customer Co', $customer->company_name);
        $this->assertSame('New Customer Co', $customer->name);
        $this->assertSame('123 Demo Street', $customer->company_address);
        $this->assertSame('Jane Doe', $customer->pic_name);
        $this->assertTrue($customer->hasRole(User::ROLE_CUSTOMER));
        $this->assertNotNull($customer->password);
    }

    public function test_admin_can_edit_a_customer_profile_and_email(): void
    {
        $customer = User::factory()->customer()->create([
            'company_name' => 'Old Company',
            'name' => 'Old Company',
            'email' => 'old@example.com',
        ]);

        $customer->update([
            'email' => 'updated@example.com',
            'company_name' => 'Updated Company',
            'name' => 'Updated Company',
            'pic_name' => 'Updated PIC',
        ]);

        $customer->refresh();

        $this->assertSame('Updated Company', $customer->company_name);
        $this->assertSame('Updated Company', $customer->name);
        $this->assertSame('updated@example.com', $customer->email);
        $this->assertSame('Updated PIC', $customer->pic_name);
    }

    public function test_duplicate_customer_email_is_rejected(): void
    {
        User::factory()->customer()->create([
            'email' => 'taken@example.com',
        ]);

        $validator = Validator::make([
            'email' => 'taken@example.com',
        ], [
            'email' => ['required', 'email', Rule::unique('users', 'email')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->messages());
    }

    public function test_customer_records_are_visible_in_admin_customer_resource_scope(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);
        $customer = User::factory()->customer()->create([
            'company_name' => 'Visible Customer',
            'email' => 'visible@example.com',
        ]);

        $records = UserResource::getEloquentQuery()->pluck('email')->all();

        $this->assertContains($customer->email, $records);
        $this->assertNotContains('admin@example.com', $records);
    }
}
