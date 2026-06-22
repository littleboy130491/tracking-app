<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_customer_cannot_access_filament_panel(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_filament_login(): void
    {
        $this->get('/admin')
            ->assertRedirectToRoute('filament.admin.auth.login');
    }
}
