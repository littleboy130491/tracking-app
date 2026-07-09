<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\BillOfLadingMilestoneState;
use App\Models\User;
use App\Services\BillOfLadingWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BillOfLadingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_bl_seeds_pre_lane_milestones_on_create(): void
    {
        $billOfLading = BillOfLading::factory()->create([
            'shipment_type' => BillOfLading::TYPE_IMPORT,
        ]);

        $this->assertGreaterThan(0, $billOfLading->milestoneStates()->count());
        $this->assertSame(
            'receive_docs',
            $billOfLading->fresh()->current_milestone_key,
        );
        $this->assertTrue(
            $billOfLading->milestoneStates()
                ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
                ->exists()
        );
    }

    public function test_assigning_yellow_lane_appends_yellow_milestones(): void
    {
        $admin = User::factory()->admin()->create();
        $workflow = app(BillOfLadingWorkflowService::class);
        $billOfLading = BillOfLading::factory()->create([
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'status' => BillOfLading::STATUS_PENDING,
        ]);

        $workflow->advanceToMilestone($billOfLading->fresh(), 'pib_response', $admin->id);
        $workflow->completeCurrentMilestone($billOfLading->fresh(), [
            'note' => 'PIB response received.',
        ], $admin->id);
        $workflow->assignCustomsLane($billOfLading->fresh(), 'yellow', $admin->id);

        $billOfLading->refresh();

        $this->assertSame('yellow', $billOfLading->customs_lane);
        $this->assertTrue(
            $billOfLading->milestoneStates()
                ->where('workflow_key', 'import.yellow')
                ->where('milestone_key', 'lane_notice')
                ->exists()
        );
        $this->assertTrue(
            $billOfLading->milestoneStates()
                ->where('workflow_key', 'import.yellow')
                ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
                ->exists()
        );
    }

    public function test_export_bl_uses_export_milestones(): void
    {
        $billOfLading = BillOfLading::factory()->export()->create();

        $this->assertTrue(
            $billOfLading->milestoneStates()
                ->where('workflow_key', 'export')
                ->where('milestone_key', 'process_peb')
                ->exists()
        );
    }

    public function test_containers_can_be_attached_to_bl(): void
    {
        $billOfLading = BillOfLading::factory()->create();

        $billOfLading->containers()->create([
            'container_number' => 'TESTU1234567',
            'seal_number' => 'SEAL1',
            'container_type' => "40'HC",
            'sort_order' => 1,
        ]);

        $this->assertSame(1, $billOfLading->containers()->count());
        $this->assertDatabaseHas('bill_of_lading_containers', [
            'bill_of_lading_id' => $billOfLading->id,
            'container_number' => 'TESTU1234567',
        ]);
    }

    public function test_customs_lane_requires_completed_pib_response(): void
    {
        $admin = User::factory()->admin()->create();
        $billOfLading = BillOfLading::factory()->create([
            'shipment_type' => BillOfLading::TYPE_IMPORT,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(BillOfLadingWorkflowService::class)->assignCustomsLane($billOfLading, 'green', $admin->id);
    }

    public function test_panel_user_without_milestone_role_cannot_advance_current_milestone(): void
    {
        $panelUser = User::factory()->withRole(User::ROLE_PANEL_USER)->create();
        $billOfLading = BillOfLading::factory()->create([
            'shipment_type' => BillOfLading::TYPE_IMPORT,
        ]);

        $this->expectException(AuthorizationException::class);

        app(BillOfLadingWorkflowService::class)->completeCurrentMilestone($billOfLading, [
            'note' => 'Attempted update.',
        ], $panelUser->id);
    }

    public function test_panel_user_with_matching_milestone_role_can_advance_current_milestone(): void
    {
        $panelUser = User::factory()->withRole('workflow_documents')->create();
        $billOfLading = BillOfLading::factory()->create([
            'shipment_type' => BillOfLading::TYPE_IMPORT,
        ]);

        app(BillOfLadingWorkflowService::class)->completeCurrentMilestone($billOfLading, [
            'note' => 'Documents received.',
        ], $panelUser->id);

        $this->assertDatabaseHas('bill_of_lading_milestone_states', [
            'bill_of_lading_id' => $billOfLading->id,
            'milestone_key' => 'receive_docs',
            'state' => BillOfLadingMilestoneState::STATE_COMPLETED,
        ]);
    }

    public function test_bl_delete_retention_uses_three_year_input_date_window(): void
    {
        $retained = BillOfLading::factory()->create([
            'input_date' => now()->subYears(2)->toDateString(),
        ]);
        $expired = BillOfLading::factory()->create([
            'input_date' => now()->subYears(4)->toDateString(),
        ]);

        $this->assertTrue($retained->isWithinRetentionWindow());
        $this->assertFalse($retained->canBeDeletedAfterRetention());
        $this->assertFalse($expired->isWithinRetentionWindow());
        $this->assertTrue($expired->canBeDeletedAfterRetention());
    }
}
