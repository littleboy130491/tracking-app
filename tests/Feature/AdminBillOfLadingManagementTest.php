<?php

namespace Tests\Feature;

use App\Filament\Resources\BillOfLadings\Pages\CreateBillOfLading;
use App\Filament\Resources\BillOfLadings\Pages\EditBillOfLading;
use App\Filament\Resources\BillOfLadings\Pages\ListBillOfLadings;
use App\Models\BillOfLading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminBillOfLadingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_bl_for_customer_a(): void
    {
        $admin = User::factory()->admin()->create();
        $customerA = User::factory()->customer()->create(['name' => 'Customer A']);

        $this->actingAs($admin);

        Livewire::test(CreateBillOfLading::class)
            ->fillForm([
                'bl_number' => 'BL-CUSTOMER-A-001',
                'customer_id' => $customerA->id,
                'shipment_description' => 'Shipment for customer A',
                'input_date' => '2026-06-15',
                'status' => 'Pending',
                'phase' => 'Input',
                'gps_tracking_url' => null,
                'note' => 'Created for customer A.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bill_of_ladings', [
            'bl_number' => 'BL-CUSTOMER-A-001',
            'customer_id' => $customerA->id,
        ]);
    }

    public function test_admin_can_create_a_bl_for_customer_b(): void
    {
        $admin = User::factory()->admin()->create();
        $customerB = User::factory()->customer()->create(['name' => 'Customer B']);

        $this->actingAs($admin);

        Livewire::test(CreateBillOfLading::class)
            ->fillForm([
                'bl_number' => 'BL-CUSTOMER-B-001',
                'customer_id' => $customerB->id,
                'shipment_description' => 'Shipment for customer B',
                'input_date' => '2026-06-16',
                'status' => 'In Progress',
                'phase' => 'Transit',
                'gps_tracking_url' => null,
                'note' => 'Created for customer B.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bill_of_ladings', [
            'bl_number' => 'BL-CUSTOMER-B-001',
            'customer_id' => $customerB->id,
        ]);
    }

    public function test_admin_cannot_create_two_bl_records_with_the_same_bl_number(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        BillOfLading::factory()->create([
            'bl_number' => 'BL-DUPLICATE-ADMIN',
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateBillOfLading::class)
            ->fillForm([
                'bl_number' => 'BL-DUPLICATE-ADMIN',
                'customer_id' => $customer->id,
                'shipment_description' => 'Duplicate attempt',
                'input_date' => '2026-06-17',
                'status' => 'Pending',
                'phase' => 'Input',
            ])
            ->call('create')
            ->assertHasFormErrors(['bl_number']);
    }

    public function test_admin_can_update_status_and_phase(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => 'Pending',
            'phase' => 'Input',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditBillOfLading::class, [
            'record' => $billOfLading->getKey(),
        ])
            ->fillForm([
                'bl_number' => $billOfLading->bl_number,
                'customer_id' => $billOfLading->customer_id,
                'shipment_description' => $billOfLading->shipment_description,
                'input_date' => $billOfLading->input_date->format('Y-m-d'),
                'status' => 'Completed',
                'phase' => 'Closed',
                'gps_tracking_url' => $billOfLading->gps_tracking_url,
                'note' => $billOfLading->note,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $billOfLading->refresh();

        $this->assertSame('Completed', $billOfLading->status);
        $this->assertSame('Closed', $billOfLading->phase);
    }

    public function test_admin_can_save_a_valid_gps_url(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'gps_tracking_url' => null,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditBillOfLading::class, [
            'record' => $billOfLading->getKey(),
        ])
            ->fillForm([
                'bl_number' => $billOfLading->bl_number,
                'customer_id' => $billOfLading->customer_id,
                'shipment_description' => $billOfLading->shipment_description,
                'input_date' => $billOfLading->input_date->format('Y-m-d'),
                'status' => $billOfLading->status,
                'phase' => $billOfLading->phase,
                'gps_tracking_url' => 'https://maps.google.com/?q=Jakarta+Port',
                'note' => $billOfLading->note,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $billOfLading->refresh();

        $this->assertSame('https://maps.google.com/?q=Jakarta+Port', $billOfLading->gps_tracking_url);
    }

    public function test_admin_can_find_a_bl_by_bl_number(): void
    {
        $admin = User::factory()->admin()->create();
        $target = BillOfLading::factory()->create([
            'bl_number' => 'BL-SEARCH-TARGET',
        ]);
        BillOfLading::factory()->create([
            'bl_number' => 'BL-OTHER-RECORD',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListBillOfLadings::class)
            ->searchTable('SEARCH-TARGET')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords(
                BillOfLading::query()->where('bl_number', 'BL-OTHER-RECORD')->get()
            );
    }

    public function test_admin_can_filter_bl_records_by_status_phase_and_input_date(): void
    {
        $admin = User::factory()->admin()->create();

        $pending = BillOfLading::factory()->create([
            'bl_number' => 'BL-FILTER-PENDING',
            'status' => 'Pending',
            'phase' => 'Input',
            'input_date' => '2026-03-10',
        ]);
        $completed = BillOfLading::factory()->create([
            'bl_number' => 'BL-FILTER-COMPLETED',
            'status' => 'Completed',
            'phase' => 'Closed',
            'input_date' => '2026-06-15',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListBillOfLadings::class)
            ->filterTable('status', 'Pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$completed])
            ->resetTableFilters()
            ->filterTable('phase', 'Closed')
            ->assertCanSeeTableRecords([$completed])
            ->assertCanNotSeeTableRecords([$pending])
            ->resetTableFilters()
            ->filterTable('month', '3')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$completed])
            ->resetTableFilters()
            ->filterTable('year', '2026')
            ->assertCanSeeTableRecords([$pending, $completed])
            ->resetTableFilters()
            ->filterTable('input_date', [
                'date' => '2026-06-15',
            ])
            ->assertCanSeeTableRecords([$completed])
            ->assertCanNotSeeTableRecords([$pending]);
    }
}
