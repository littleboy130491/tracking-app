<?php

namespace Tests\Feature;

use App\Filament\Resources\StaffMembers\Pages\CreateStaffMember;
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

    public function test_active_panel_user_can_access_admin_but_inactive_panel_user_cannot(): void
    {
        $active = User::factory()->withRole(User::ROLE_PANEL_USER)->create();
        $inactive = User::factory()->withRole(User::ROLE_PANEL_USER)->create(['is_active' => false]);

        $this->actingAs($active)->get('/admin')->assertOk();
        $this->actingAs($inactive)->get('/admin')->assertForbidden();
    }

    public function test_staff_access_setup_assigns_panel_and_workflow_roles(): void
    {
        $staff = User::factory()->create();

        CreateStaffMember::syncAccessRoles($staff, ['workflow_documents', 'workflow_customs']);

        $this->assertTrue($staff->hasRole(User::ROLE_PANEL_USER));
        $this->assertTrue($staff->hasRole('workflow_documents'));
        $this->assertTrue($staff->hasRole('workflow_customs'));
        $this->assertTrue($staff->canAccessPanel(filament()->getPanel('admin')));
    }
}
