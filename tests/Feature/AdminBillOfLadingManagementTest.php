<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class AdminBillOfLadingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_bl_for_customer_a(): void
    {
        $customerA = User::factory()->customer()->create(['name' => 'Customer A']);

        $billOfLading = BillOfLading::factory()->create([
            'bl_number' => 'BL-CUSTOMER-A-001',
            'customer_id' => $customerA->id,
            'shipment_description' => 'Shipment for customer A',
            'input_date' => '2026-06-15',
            'status' => BillOfLading::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('bill_of_ladings', [
            'bl_number' => 'BL-CUSTOMER-A-001',
            'customer_id' => $customerA->id,
        ]);
        $this->assertSame('receive_docs', $billOfLading->fresh()->current_milestone_key);
    }

    public function test_admin_can_create_a_bl_for_customer_b(): void
    {
        $customerB = User::factory()->customer()->create(['name' => 'Customer B']);

        BillOfLading::factory()->create([
            'bl_number' => 'BL-CUSTOMER-B-001',
            'customer_id' => $customerB->id,
            'shipment_description' => 'Shipment for customer B',
            'input_date' => '2026-06-16',
            'status' => BillOfLading::STATUS_IN_PROGRESS,
        ]);

        $this->assertDatabaseHas('bill_of_ladings', [
            'bl_number' => 'BL-CUSTOMER-B-001',
            'customer_id' => $customerB->id,
        ]);
    }

    public function test_admin_cannot_create_two_bl_records_with_the_same_bl_number(): void
    {
        $customer = User::factory()->customer()->create();
        BillOfLading::factory()->create([
            'bl_number' => 'BL-DUPLICATE-ADMIN',
            'customer_id' => $customer->id,
        ]);

        $validator = Validator::make([
            'bl_number' => 'BL-DUPLICATE-ADMIN',
        ], [
            'bl_number' => ['required', Rule::unique('bill_of_ladings', 'bl_number')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('bl_number', $validator->errors()->messages());
    }

    public function test_admin_can_update_status_without_overriding_workflow_phase(): void
    {
        $billOfLading = BillOfLading::factory()->create([
            'status' => BillOfLading::STATUS_PENDING,
        ]);
        $workflowPhase = $billOfLading->fresh()->phase;

        $billOfLading->update([
            'status' => BillOfLading::STATUS_COMPLETED,
        ]);

        $billOfLading->refresh();

        $this->assertSame(BillOfLading::STATUS_COMPLETED, $billOfLading->status);
        $this->assertSame($workflowPhase, $billOfLading->phase);
    }

    public function test_admin_can_save_a_valid_gps_url(): void
    {
        $billOfLading = BillOfLading::factory()->create([
            'gps_tracking_url' => null,
        ]);

        $billOfLading->update([
            'gps_tracking_url' => 'https://maps.google.com/?q=Jakarta+Port',
        ]);

        $this->assertSame('https://maps.google.com/?q=Jakarta+Port', $billOfLading->fresh()->gps_tracking_url);
    }

    public function test_admin_can_find_a_bl_by_bl_number(): void
    {
        $target = BillOfLading::factory()->create([
            'bl_number' => 'BL-SEARCH-TARGET',
        ]);
        BillOfLading::factory()->create([
            'bl_number' => 'BL-OTHER-RECORD',
        ]);

        $records = BillOfLading::query()
            ->where('bl_number', 'like', '%SEARCH-TARGET%')
            ->pluck('id')
            ->all();

        $this->assertContains($target->id, $records);
        $this->assertCount(1, $records);
    }

    public function test_admin_can_filter_bl_records_by_status_milestone_and_input_date(): void
    {
        $pending = BillOfLading::factory()->create([
            'bl_number' => 'BL-FILTER-PENDING',
            'status' => BillOfLading::STATUS_PENDING,
            'input_date' => '2026-03-10',
        ]);
        $pending->forceFill([
            'phase' => 'Proses DO',
            'current_milestone_key' => 'process_do',
        ])->saveQuietly();

        $completed = BillOfLading::factory()->create([
            'bl_number' => 'BL-FILTER-COMPLETED',
            'status' => BillOfLading::STATUS_COMPLETED,
            'input_date' => '2026-06-15',
        ]);
        $completed->forceFill([
            'phase' => 'Penerimaan dokumen customer',
            'current_milestone_key' => 'receive_docs',
        ])->saveQuietly();

        $this->assertSame([$pending->id], BillOfLading::query()
            ->where('status', BillOfLading::STATUS_PENDING)
            ->pluck('id')
            ->all());

        $this->assertSame([$completed->id], BillOfLading::query()
            ->where('current_milestone_key', 'receive_docs')
            ->pluck('id')
            ->all());

        $this->assertSame([$pending->id], BillOfLading::query()
            ->whereMonth('input_date', 3)
            ->pluck('id')
            ->all());

        $this->assertEqualsCanonicalizing([$pending->id, $completed->id], BillOfLading::query()
            ->whereYear('input_date', 2026)
            ->pluck('id')
            ->all());

        $this->assertSame([$completed->id], BillOfLading::query()
            ->whereDate('input_date', '2026-06-15')
            ->pluck('id')
            ->all());
    }
}
