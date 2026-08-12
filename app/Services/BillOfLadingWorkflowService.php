<?php

namespace App\Services;

use App\Models\BillOfLading;
use App\Models\BillOfLadingMilestoneState;
use App\Models\BillOfLadingUpdate;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillOfLadingWorkflowService
{
    public function seedInitialMilestones(BillOfLading $billOfLading): void
    {
        $definitions = $this->initialDefinitions($billOfLading->shipment_type);

        $this->replaceWorkflowMilestones(
            billOfLading: $billOfLading,
            workflowKey: $billOfLading->shipment_type,
            definitions: $definitions,
            startSequence: 1,
            markFirstInProgress: true,
        );

        $first = $billOfLading->milestoneStates()
            ->where('workflow_key', $billOfLading->shipment_type)
            ->orderBy('sequence')
            ->first();

        $billOfLading->forceFill([
            'current_milestone_key' => $first?->milestone_key,
            'phase' => $first?->label ?? $billOfLading->phase,
            'status' => BillOfLading::STATUS_IN_PROGRESS,
        ])->saveQuietly();
    }

    public function assignCustomsLane(BillOfLading $billOfLading, string $lane, ?int $userId = null, ?string $note = null): void
    {
        if ($billOfLading->shipment_type !== BillOfLading::TYPE_IMPORT) {
            throw new InvalidArgumentException('Customs lane can only be assigned to import shipments.');
        }

        if (! array_key_exists($lane, config('bl_workflows.customs_lanes', []))) {
            throw new InvalidArgumentException("Unknown customs lane [{$lane}].");
        }

        if (filled($billOfLading->customs_lane) && $billOfLading->customs_lane !== $lane) {
            throw new InvalidArgumentException('Customs lane is already assigned for this BL.');
        }

        if ($billOfLading->customs_lane === $lane) {
            return;
        }

        if (! $this->canAssignCustomsLane($billOfLading)) {
            throw new InvalidArgumentException('Customs lane can only be assigned after PIB response is completed.');
        }

        $this->authorizeMilestone($userId, 'pib_response');

        DB::transaction(function () use ($billOfLading, $lane, $userId, $note): void {
            $definitions = config("bl_workflows.import_lanes.{$lane}", []);
            $startSequence = ((int) $billOfLading->milestoneStates()->max('sequence')) + 1;
            $needsInProgress = $billOfLading->milestoneStates()
                ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
                ->doesntExist();

            $this->appendMilestones(
                billOfLading: $billOfLading,
                workflowKey: "import.{$lane}",
                definitions: $definitions,
                startSequence: $startSequence,
                markFirstInProgress: $needsInProgress,
            );

            $firstLaneMilestone = $billOfLading->milestoneStates()
                ->where('workflow_key', "import.{$lane}")
                ->orderBy('sequence')
                ->first();

            $billOfLading->forceFill([
                'customs_lane' => $lane,
                'status' => BillOfLading::STATUS_IN_PROGRESS,
                'current_milestone_key' => $needsInProgress
                    ? ($firstLaneMilestone?->milestone_key ?? $billOfLading->current_milestone_key)
                    : $billOfLading->current_milestone_key,
                'phase' => $needsInProgress
                    ? ($firstLaneMilestone?->label ?? $billOfLading->phase)
                    : $billOfLading->phase,
            ])->save();

            $this->writeHistory(
                billOfLading: $billOfLading,
                userId: $userId,
                note: $note ?: 'Customs lane assigned: '.config("bl_workflows.customs_lanes.{$lane}"),
                milestoneKey: $billOfLading->current_milestone_key,
                visibility: BillOfLadingUpdate::VISIBILITY_CUSTOMER,
            );
        });
    }

    /**
     * @param  array{note?: string|null, visibility?: string, status?: string}  $attributes
     */
    public function completeCurrentMilestone(BillOfLading $billOfLading, array $attributes = [], ?int $userId = null): BillOfLadingMilestoneState
    {
        return DB::transaction(function () use ($billOfLading, $attributes, $userId): BillOfLadingMilestoneState {
            if ($billOfLading->status === BillOfLading::STATUS_CANCELLED) {
                throw new InvalidArgumentException('A cancelled BL must be resumed before its milestone can advance.');
            }

            $current = $billOfLading->milestoneStates()
                ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
                ->orderBy('sequence')
                ->first();

            if (! $current) {
                $current = $billOfLading->milestoneStates()
                    ->where('state', BillOfLadingMilestoneState::STATE_PENDING)
                    ->orderBy('sequence')
                    ->first();
            }

            if (! $current) {
                throw new InvalidArgumentException('No milestone available to complete.');
            }

            $visibility = $attributes['visibility'] ?? BillOfLadingUpdate::VISIBILITY_CUSTOMER;
            $customerNote = $visibility === BillOfLadingUpdate::VISIBILITY_CUSTOMER
                ? ($attributes['note'] ?? $billOfLading->customer_note)
                : $billOfLading->customer_note;

            $this->authorizeMilestone($userId, $current->milestone_key);

            $current->forceFill([
                'state' => BillOfLadingMilestoneState::STATE_COMPLETED,
                'completed_at' => now(),
            ])->save();

            $next = $billOfLading->milestoneStates()
                ->where('state', BillOfLadingMilestoneState::STATE_PENDING)
                ->orderBy('sequence')
                ->first();

            if ($next) {
                $next->forceFill([
                    'state' => BillOfLadingMilestoneState::STATE_IN_PROGRESS,
                ])->save();

                $billOfLading->forceFill([
                    'current_milestone_key' => $next->milestone_key,
                    'phase' => $next->label,
                    'status' => BillOfLading::STATUS_IN_PROGRESS,
                    'customer_note' => $customerNote,
                ])->save();
            } elseif (
                $billOfLading->shipment_type === BillOfLading::TYPE_IMPORT
                && blank($billOfLading->customs_lane)
            ) {
                $billOfLading->forceFill([
                    'current_milestone_key' => $current->milestone_key,
                    'phase' => 'Awaiting customs lane',
                    'status' => BillOfLading::STATUS_IN_PROGRESS,
                    'customer_note' => $customerNote,
                ])->save();
            } else {
                $billOfLading->forceFill([
                    'current_milestone_key' => $current->milestone_key,
                    'phase' => $current->label,
                    'status' => BillOfLading::STATUS_COMPLETED,
                    'customer_note' => $customerNote,
                ])->save();
            }

            $this->writeHistory(
                billOfLading: $billOfLading->fresh(),
                userId: $userId,
                note: $attributes['note'] ?? ('Completed: '.$current->label),
                milestoneKey: $current->milestone_key,
                visibility: $visibility,
            );

            return $current->fresh();
        });
    }

    public function activateDeliveryTrack(BillOfLading $billOfLading, ?int $userId = null, ?string $note = null): void
    {
        if ($billOfLading->milestoneStates()->where('workflow_key', 'delivery')->exists()) {
            return;
        }

        if (! $this->canActivateDeliveryTrack($billOfLading)) {
            throw new InvalidArgumentException('Delivery can only start after the primary shipment workflow is complete.');
        }

        $this->authorizeMilestone($userId, 'finalize_docs');

        DB::transaction(function () use ($billOfLading, $userId, $note): void {
            $startSequence = ((int) $billOfLading->milestoneStates()->max('sequence')) + 1;

            $this->appendMilestones(
                billOfLading: $billOfLading,
                workflowKey: 'delivery',
                definitions: config('bl_workflows.delivery', []),
                startSequence: $startSequence,
                markFirstInProgress: $billOfLading->milestoneStates()
                    ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
                    ->doesntExist(),
            );

            if ($billOfLading->milestoneStates()->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)->doesntExist()) {
                $firstDelivery = $billOfLading->milestoneStates()
                    ->where('workflow_key', 'delivery')
                    ->orderBy('sequence')
                    ->first();

                if ($firstDelivery) {
                    $firstDelivery->forceFill([
                        'state' => BillOfLadingMilestoneState::STATE_IN_PROGRESS,
                    ])->save();

                    $billOfLading->forceFill([
                        'current_milestone_key' => $firstDelivery->milestone_key,
                        'phase' => $firstDelivery->label,
                        'status' => BillOfLading::STATUS_IN_PROGRESS,
                    ])->save();
                }
            }

            $this->writeHistory(
                billOfLading: $billOfLading->fresh(),
                userId: $userId,
                note: $note ?: 'Delivery track activated.',
                milestoneKey: $billOfLading->fresh()->current_milestone_key,
                visibility: BillOfLadingUpdate::VISIBILITY_CUSTOMER,
            );
        });
    }

    /**
     * Advance a BL to a target milestone key for seeding/demo purposes.
     */
    public function advanceToMilestone(BillOfLading $billOfLading, string $targetMilestoneKey, ?int $userId = null): void
    {
        $guard = 0;

        while ($guard < 50) {
            $current = $billOfLading->fresh()->milestoneStates()
                ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
                ->orderBy('sequence')
                ->first();

            if (! $current) {
                break;
            }

            if ($current->milestone_key === $targetMilestoneKey) {
                break;
            }

            $this->completeCurrentMilestone($billOfLading->fresh(), [
                'note' => 'Advanced to next milestone.',
            ], $userId);

            $guard++;
        }
    }

    /**
     * @return list<array{key: string, label: string, customer_label?: string, customer_visible?: bool}>
     */
    private function initialDefinitions(string $shipmentType): array
    {
        return match ($shipmentType) {
            BillOfLading::TYPE_EXPORT => config('bl_workflows.export', []),
            default => config('bl_workflows.import_pre_lane', []),
        };
    }

    /**
     * @param  list<array{key: string, label: string, customer_label?: string, customer_visible?: bool}>  $definitions
     */
    private function replaceWorkflowMilestones(
        BillOfLading $billOfLading,
        string $workflowKey,
        array $definitions,
        int $startSequence,
        bool $markFirstInProgress = false,
    ): void {
        $billOfLading->milestoneStates()
            ->where('workflow_key', $workflowKey)
            ->delete();

        $this->appendMilestones(
            billOfLading: $billOfLading,
            workflowKey: $workflowKey,
            definitions: $definitions,
            startSequence: $startSequence,
            markFirstInProgress: $markFirstInProgress,
        );
    }

    /**
     * @param  list<array{key: string, label: string, customer_label?: string, customer_visible?: bool}>  $definitions
     */
    private function appendMilestones(
        BillOfLading $billOfLading,
        string $workflowKey,
        array $definitions,
        int $startSequence,
        bool $markFirstInProgress = false,
    ): void {
        foreach (array_values($definitions) as $index => $definition) {
            $billOfLading->milestoneStates()->create([
                'workflow_key' => $workflowKey,
                'milestone_key' => $definition['key'],
                'sequence' => $startSequence + $index,
                'label' => $definition['label'],
                'customer_label' => $definition['customer_label'] ?? $definition['label'],
                'state' => $markFirstInProgress && $index === 0
                    ? BillOfLadingMilestoneState::STATE_IN_PROGRESS
                    : BillOfLadingMilestoneState::STATE_PENDING,
                'customer_visible' => $definition['customer_visible'] ?? true,
            ]);
        }
    }

    private function writeHistory(
        BillOfLading $billOfLading,
        ?int $userId,
        string $note,
        ?string $milestoneKey,
        string $visibility,
    ): void {
        $billOfLading->updates()->create([
            'user_id' => $userId,
            'status' => $billOfLading->status,
            'phase' => $billOfLading->phase,
            'milestone_key' => $milestoneKey,
            'visibility' => $visibility,
            'note' => $note,
        ]);
    }

    public function canAssignCustomsLane(BillOfLading $billOfLading): bool
    {
        if ($billOfLading->shipment_type !== BillOfLading::TYPE_IMPORT || filled($billOfLading->customs_lane)) {
            return false;
        }

        return $billOfLading->milestoneStates()
            ->where('milestone_key', 'pib_response')
            ->where('state', BillOfLadingMilestoneState::STATE_COMPLETED)
            ->exists();
    }

    public function canActivateDeliveryTrack(BillOfLading $billOfLading): bool
    {
        if (
            $billOfLading->status === BillOfLading::STATUS_CANCELLED
            || $billOfLading->milestoneStates()->where('workflow_key', 'delivery')->exists()
        ) {
            return false;
        }

        $requiredMilestone = $billOfLading->shipment_type === BillOfLading::TYPE_EXPORT
            ? 'stock_to_port'
            : 'deliver_container';

        return $billOfLading->milestoneStates()
            ->where('milestone_key', $requiredMilestone)
            ->where('state', BillOfLadingMilestoneState::STATE_COMPLETED)
            ->exists();
    }

    /**
     * @param  array{status: string, note?: string|null, visibility?: string}  $attributes
     */
    public function postProgressUpdate(
        BillOfLading $billOfLading,
        array $attributes,
        ?int $userId = null,
    ): BillOfLadingUpdate {
        $allowedStatuses = [
            BillOfLading::STATUS_IN_PROGRESS,
            BillOfLading::STATUS_ON_HOLD,
            BillOfLading::STATUS_CANCELLED,
        ];

        if (! in_array($attributes['status'], $allowedStatuses, true)) {
            throw new InvalidArgumentException('Progress updates cannot set this BL status.');
        }

        $this->authorizeMilestone($userId, $billOfLading->current_milestone_key);

        return DB::transaction(function () use ($billOfLading, $attributes, $userId): BillOfLadingUpdate {
            $visibility = $attributes['visibility'] ?? BillOfLadingUpdate::VISIBILITY_CUSTOMER;

            $billOfLading->update([
                'status' => $attributes['status'],
                'customer_note' => $visibility === BillOfLadingUpdate::VISIBILITY_CUSTOMER
                    ? ($attributes['note'] ?? $billOfLading->customer_note)
                    : $billOfLading->customer_note,
            ]);

            return $billOfLading->updates()->create([
                'user_id' => $userId,
                'status' => $attributes['status'],
                'phase' => $billOfLading->phase,
                'milestone_key' => $billOfLading->current_milestone_key,
                'visibility' => $visibility,
                'note' => $attributes['note'] ?? null,
            ]);
        });
    }

    public function userCanManageCurrentMilestone(User $user, BillOfLading $billOfLading): bool
    {
        $milestoneKey = $billOfLading->milestoneStates()
            ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
            ->orderBy('sequence')
            ->value('milestone_key') ?? $billOfLading->current_milestone_key;

        return $user->canManageMilestone($milestoneKey);
    }

    private function authorizeMilestone(?int $userId, ?string $milestoneKey): void
    {
        if ($userId === null) {
            return;
        }

        $user = User::query()->find($userId);

        if (! $user || ! $user->canManageMilestone($milestoneKey)) {
            throw new AuthorizationException('You are not assigned to update this milestone.');
        }
    }
}
