<?php

namespace Tests\Feature;

use App\Filament\Resources\BillOfLadings\Pages\EditBillOfLading;
use App\Filament\Resources\BillOfLadings\Pages\ViewBillOfLading;
use App\Models\BillOfLading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillOfLadingAdminHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_status_phase_or_note_update_creates_history_row(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => 'Pending',
            'phase' => 'Input',
            'note' => 'Initial note.',
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
                'status' => 'In Progress',
                'phase' => 'Transit',
                'gps_tracking_url' => $billOfLading->gps_tracking_url,
                'note' => 'Moved to transit.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $billOfLading->refresh();

        $this->assertSame('In Progress', $billOfLading->status);
        $this->assertSame('Transit', $billOfLading->phase);
        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'user_id' => $admin->id,
            'status' => 'In Progress',
            'phase' => 'Transit',
            'note' => 'Moved to transit.',
        ]);
    }

    public function test_multiple_updates_appear_in_chronological_order(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => 'Pending',
            'phase' => 'Input',
            'note' => 'First note.',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditBillOfLading::class, [
            'record' => $billOfLading->getKey(),
        ])
            ->fillForm($this->formData($billOfLading, [
                'status' => 'In Progress',
                'phase' => 'Customs',
                'note' => 'Second note.',
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $billOfLading->refresh();

        Livewire::test(EditBillOfLading::class, [
            'record' => $billOfLading->getKey(),
        ])
            ->fillForm($this->formData($billOfLading, [
                'status' => 'In Progress',
                'phase' => 'Transit',
                'note' => 'Third note.',
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $notes = $billOfLading->fresh()->updates->pluck('note')->all();

        $this->assertSame(['Second note.', 'Third note.'], $notes);
        $this->assertTrue(
            $billOfLading->fresh()->updates->first()->created_at->lessThanOrEqualTo(
                $billOfLading->fresh()->updates->last()->created_at
            )
        );
    }

    public function test_history_records_preserve_old_tracking_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => 'Pending',
            'phase' => 'Input',
            'note' => 'Original note.',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditBillOfLading::class, [
            'record' => $billOfLading->getKey(),
        ])
            ->fillForm($this->formData($billOfLading, [
                'status' => 'In Progress',
                'phase' => 'Customs',
                'note' => 'Updated note.',
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'note' => 'Updated note.',
        ]);

        $billOfLading->refresh();

        Livewire::test(EditBillOfLading::class, [
            'record' => $billOfLading->getKey(),
        ])
            ->fillForm($this->formData($billOfLading, [
                'status' => 'On Hold',
                'phase' => 'Customs',
                'note' => 'Latest note.',
            ]))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'note' => 'Updated note.',
        ]);
        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'note' => 'Latest note.',
        ]);
        $this->assertSame('Latest note.', $billOfLading->fresh()->note);
    }

    public function test_admin_can_post_progress_update_from_bl_view_page(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => 'Pending',
            'phase' => 'Input',
            'note' => 'Initial note.',
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewBillOfLading::class, [
            'record' => $billOfLading->getKey(),
        ])
            ->callAction('postProgressUpdate', data: [
                'status' => 'In Progress',
                'phase' => 'Transit',
                'note' => 'Posted from view page.',
            ])
            ->assertNotified();

        $billOfLading->refresh();

        $this->assertSame('In Progress', $billOfLading->status);
        $this->assertSame('Transit', $billOfLading->phase);
        $this->assertSame('Posted from view page.', $billOfLading->note);
        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'user_id' => $admin->id,
            'note' => 'Posted from view page.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formData(BillOfLading $billOfLading, array $overrides = []): array
    {
        return array_merge([
            'bl_number' => $billOfLading->bl_number,
            'customer_id' => $billOfLading->customer_id,
            'shipment_description' => $billOfLading->shipment_description,
            'origin' => $billOfLading->origin,
            'destination' => $billOfLading->destination,
            'items_description' => $billOfLading->items_description,
            'quantity' => $billOfLading->quantity,
            'gross_weight_kg' => $billOfLading->gross_weight_kg,
            'volume_cbm' => $billOfLading->volume_cbm,
            'input_date' => $billOfLading->input_date->format('Y-m-d'),
            'status' => $billOfLading->status,
            'phase' => $billOfLading->phase,
            'gps_tracking_url' => $billOfLading->gps_tracking_url,
            'note' => $billOfLading->note,
        ], $overrides);
    }
}
