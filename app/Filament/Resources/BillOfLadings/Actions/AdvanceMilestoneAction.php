<?php

namespace App\Filament\Resources\BillOfLadings\Actions;

use App\Models\BillOfLading;
use App\Models\BillOfLadingMilestoneState;
use App\Models\BillOfLadingUpdate;
use App\Services\BillOfLadingWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class AdvanceMilestoneAction
{
    public static function make(): Action
    {
        return Action::make('advanceMilestone')
            ->label('Advance Milestone')
            ->icon(Heroicon::OutlinedArrowRightCircle)
            ->color('success')
            ->visible(fn (BillOfLading $record): bool => auth()->user()?->canManageMilestone(
                $record->milestoneStates()
                    ->where('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
                    ->first()?->milestone_key ?? $record->current_milestone_key,
            ) ?? false)
            ->modalHeading('Advance Milestone')
            ->modalDescription('Complete the current milestone and move the BL to the next step.')
            ->modalSubmitActionLabel('Complete current step')
            ->schema([
                Select::make('status')
                    ->label('Status after update')
                    ->options(array_combine(BillOfLading::STATUSES, BillOfLading::STATUSES))
                    ->required(),
                Select::make('visibility')
                    ->label('Note visibility')
                    ->options(config('bl_workflows.visibilities'))
                    ->default(BillOfLadingUpdate::VISIBILITY_CUSTOMER)
                    ->required(),
                Textarea::make('note')
                    ->label('Update note')
                    ->placeholder('Describe what was completed.')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->fillForm(fn (BillOfLading $record): array => [
                'status' => $record->status === BillOfLading::STATUS_PENDING
                    ? BillOfLading::STATUS_IN_PROGRESS
                    : $record->status,
                'visibility' => BillOfLadingUpdate::VISIBILITY_CUSTOMER,
                'note' => '',
            ])
            ->action(function (array $data, BillOfLading $record): void {
                app(BillOfLadingWorkflowService::class)->completeCurrentMilestone($record, $data, auth()->id());
            })
            ->successNotificationTitle('Milestone advanced');
    }
}
