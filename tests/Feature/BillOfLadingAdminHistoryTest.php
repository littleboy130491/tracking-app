<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\User;
use App\Services\BillOfLadingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillOfLadingAdminHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_status_or_note_update_creates_history_row_without_overriding_workflow_phase(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => BillOfLading::STATUS_PENDING,
            'customer_note' => 'Initial note.',
        ]);
        $workflowPhase = $billOfLading->fresh()->phase;

        $billOfLading->postProgressUpdate([
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'note' => 'Moved to transit.',
        ], $admin->id);

        $billOfLading->refresh();

        $this->assertSame(BillOfLading::STATUS_IN_PROGRESS, $billOfLading->status);
        $this->assertSame($workflowPhase, $billOfLading->phase);
        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'user_id' => $admin->id,
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'phase' => $workflowPhase,
            'note' => 'Moved to transit.',
        ]);
    }

    public function test_multiple_updates_appear_in_chronological_order(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => BillOfLading::STATUS_PENDING,
            'customer_note' => 'First note.',
        ]);

        $billOfLading->postProgressUpdate([
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'note' => 'Second note.',
        ], $admin->id);
        $billOfLading->postProgressUpdate([
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'note' => 'Third note.',
        ], $admin->id);

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
            'status' => BillOfLading::STATUS_PENDING,
            'customer_note' => 'Original note.',
        ]);

        $billOfLading->postProgressUpdate([
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'note' => 'Updated note.',
        ], $admin->id);

        $billOfLading->postProgressUpdate([
            'status' => BillOfLading::STATUS_ON_HOLD,
            'note' => 'Latest note.',
        ], $admin->id);

        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'note' => 'Updated note.',
        ]);
        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'note' => 'Latest note.',
        ]);
        $this->assertSame('Latest note.', $billOfLading->fresh()->customer_note);
    }

    public function test_admin_only_update_does_not_replace_the_customer_note(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'customer_note' => 'Visible customer note.',
        ]);

        $billOfLading->postProgressUpdate([
            'status' => BillOfLading::STATUS_ON_HOLD,
            'visibility' => 'admin_only',
            'note' => 'Internal operational note.',
        ], $admin->id);

        $this->assertSame('Visible customer note.', $billOfLading->fresh()->customer_note);
        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'visibility' => 'admin_only',
            'note' => 'Internal operational note.',
        ]);
    }

    public function test_admin_can_advance_milestone_and_write_history(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'status' => BillOfLading::STATUS_PENDING,
        ]);

        app(BillOfLadingWorkflowService::class)->completeCurrentMilestone($billOfLading, [
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'note' => 'Documents received.',
        ], $admin->id);

        $billOfLading->refresh();

        $this->assertSame(BillOfLading::STATUS_IN_PROGRESS, $billOfLading->status);
        $this->assertSame('draft_pib', $billOfLading->current_milestone_key);
        $this->assertDatabaseHas('bill_of_lading_updates', [
            'bill_of_lading_id' => $billOfLading->id,
            'user_id' => $admin->id,
            'milestone_key' => 'receive_docs',
            'note' => 'Documents received.',
        ]);
    }

    public function test_sensitive_bl_edits_are_recorded_in_the_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'gps_tracking_url' => null,
        ])->refresh();

        $this->actingAs($admin);
        $billOfLading->update([
            'gps_tracking_url' => 'https://tracking.example.com/shipment/100',
        ]);

        $audit = $billOfLading->audits()
            ->where('event', 'updated')
            ->where('user_id', $admin->id)
            ->firstOrFail();

        $this->assertArrayHasKey('gps_tracking_url', $audit->changes);
        $this->assertSame(
            'https://tracking.example.com/shipment/100',
            $audit->changes['gps_tracking_url']['new'],
        );
    }
}
