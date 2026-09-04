<?php

namespace Tests\Feature;

use App\Filament\Resources\Containers\Schemas\ContainerExportFields;
use App\Models\BillOfLading;
use App\Models\Container;
use App\Models\User;
use Awcodes\Curator\Components\Forms\CuratorPicker;
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

        $billOfLading = BillOfLading::factory()->forUser($customerA)->create([
            'bl_number' => 'BL-CUSTOMER-A-001',
            'shipping_method' => BillOfLading::SHIPPING_METHOD_LCL,
            'shipment_description' => 'Shipment for customer A',
            'input_date' => '2026-06-15',
            'status' => BillOfLading::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('bill_of_ladings', [
            'bl_number' => 'BL-CUSTOMER-A-001',
            'company_id' => $customerA->companies()->first()->id,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_LCL,
        ]);
        $this->assertSame('receive_docs', $billOfLading->fresh()->current_milestone_key);
    }

    public function test_admin_can_create_a_bl_for_customer_b(): void
    {
        $customerB = User::factory()->customer()->create(['name' => 'Customer B']);

        BillOfLading::factory()->forUser($customerB)->create([
            'bl_number' => 'BL-CUSTOMER-B-001',
            'shipment_description' => 'Shipment for customer B',
            'input_date' => '2026-06-16',
            'status' => BillOfLading::STATUS_IN_PROGRESS,
        ]);

        $this->assertDatabaseHas('bill_of_ladings', [
            'bl_number' => 'BL-CUSTOMER-B-001',
            'company_id' => $customerB->companies()->first()->id,
        ]);
    }

    public function test_admin_cannot_create_two_bl_records_with_the_same_bl_number(): void
    {
        $customer = User::factory()->customer()->create();
        BillOfLading::factory()->forUser($customer)->create([
            'bl_number' => 'BL-DUPLICATE-ADMIN',
        ]);

        $validator = Validator::make([
            'bl_number' => 'BL-DUPLICATE-ADMIN',
        ], [
            'bl_number' => ['required', Rule::unique('bill_of_ladings', 'bl_number')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('bl_number', $validator->errors()->messages());
    }

    public function test_admin_can_put_a_bl_on_hold_without_overriding_workflow_phase(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => BillOfLading::STATUS_IN_PROGRESS,
        ]);
        $workflowPhase = $billOfLading->fresh()->phase;

        $billOfLading->postProgressUpdate([
            'status' => BillOfLading::STATUS_ON_HOLD,
            'note' => 'Waiting for customer confirmation.',
        ], $admin->id);

        $billOfLading->refresh();

        $this->assertSame(BillOfLading::STATUS_ON_HOLD, $billOfLading->status);
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

    public function test_admin_can_save_aju_number_and_air_shipment_container_type(): void
    {
        $billOfLading = BillOfLading::factory()->create([
            'aju_number' => null,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
        ]);

        $billOfLading->update([
            'aju_number' => '00005002123420250702',
            'shipping_method' => BillOfLading::SHIPPING_METHOD_AIR,
        ]);

        $billOfLading->refresh();

        $this->assertSame('00005002123420250702', $billOfLading->aju_number);
        $this->assertSame(BillOfLading::SHIPPING_METHOD_AIR, $billOfLading->shipping_method);
        $this->assertSame('Air Shipment', $billOfLading->shippingMethodLabel());
    }

    public function test_admin_can_save_export_tracking_fields_on_bl_and_container(): void
    {
        $billOfLading = BillOfLading::factory()->create([
            'shipment_type' => BillOfLading::TYPE_EXPORT,
        ]);
        $container = $billOfLading->containers()->create([
            'container_number' => 'EXPU5555555',
            'sort_order' => 1,
        ]);

        $billOfLading->update([
            'exporter_name' => 'PT Demo Exporter',
            'booking_order_checked' => true,
            'do_number' => 'DO-1',
            'pickup_depot' => 'Tanjung Priok Depot',
        ]);
        $container->update([
            'driver_name' => 'Joko',
            'license_number' => 'B 1111 YY',
            'stuffing_progress' => Container::STUFFING_FINISHED,
            'vgm_kg' => 18000,
            'final_checked' => true,
            'final_checked_at' => '2026-06-10',
        ]);

        $billOfLading->refresh();
        $container->refresh();

        $this->assertTrue($billOfLading->isExport());
        $this->assertSame('PT Demo Exporter', $billOfLading->exporter_name);
        $this->assertTrue($billOfLading->booking_order_checked);
        $this->assertSame('Joko', $container->driver_name);
        $this->assertSame('FINISHED', $container->stuffingProgressLabel());
        $this->assertTrue($container->final_checked);
    }

    public function test_container_documentation_photos_use_curator_pickers(): void
    {
        $fields = collect(ContainerExportFields::make())
            ->filter(fn ($field): bool => $field instanceof CuratorPicker)
            ->values();

        $this->assertCount(4, $fields);
        $this->assertSame(
            ['photo_door_id', 'photo_floor_id', 'photo_eir_id', 'photo_seal_id'],
            $fields->map(fn (CuratorPicker $field): string => $field->getName())->all(),
        );
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
        $inProgress = BillOfLading::factory()->create([
            'bl_number' => 'BL-FILTER-PROGRESS',
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'input_date' => '2026-03-10',
        ]);
        $inProgress->forceFill([
            'phase' => 'Proses DO',
            'current_milestone_key' => 'process_do',
        ])->saveQuietly();

        $onHold = BillOfLading::factory()->create([
            'bl_number' => 'BL-FILTER-HOLD',
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'input_date' => '2026-06-15',
        ]);
        $onHold->forceFill([
            'phase' => 'Penerimaan dokumen customer',
            'current_milestone_key' => 'receive_docs',
            'status' => BillOfLading::STATUS_ON_HOLD,
        ])->saveQuietly();

        $this->assertSame([$inProgress->id], BillOfLading::query()
            ->where('status', BillOfLading::STATUS_IN_PROGRESS)
            ->pluck('id')
            ->all());

        $this->assertSame([$onHold->id], BillOfLading::query()
            ->where('current_milestone_key', 'receive_docs')
            ->pluck('id')
            ->all());

        $this->assertSame([$inProgress->id], BillOfLading::query()
            ->whereMonth('input_date', 3)
            ->pluck('id')
            ->all());

        $this->assertEqualsCanonicalizing([$inProgress->id, $onHold->id], BillOfLading::query()
            ->whereYear('input_date', 2026)
            ->pluck('id')
            ->all());

        $this->assertSame([$onHold->id], BillOfLading::query()
            ->whereDate('input_date', '2026-06-15')
            ->pluck('id')
            ->all());
    }
}
