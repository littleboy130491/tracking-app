<?php

namespace App\Filament\Resources\BillOfLadings\Actions;

use App\Models\BillOfLading;
use App\Services\BillOfLadingWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class AssignCustomsLaneAction
{
    public static function make(): Action
    {
        return Action::make('assignCustomsLane')
            ->label('Assign Customs Lane')
            ->icon(Heroicon::OutlinedFlag)
            ->color('warning')
            ->visible(fn (BillOfLading $record): bool => app(BillOfLadingWorkflowService::class)->canAssignCustomsLane($record)
                && (auth()->user()?->canManageMilestone('pib_response') ?? false))
            ->modalHeading('Assign Customs Lane')
            ->modalDescription('Set Jalur Hijau / Kuning / Merah after PIB response. This appends the lane-specific milestones.')
            ->modalSubmitActionLabel('Assign lane')
            ->schema([
                Select::make('customs_lane')
                    ->label('Customs Lane')
                    ->options(config('bl_workflows.customs_lanes'))
                    ->required(),
                Textarea::make('note')
                    ->label('Note')
                    ->placeholder('Optional note for the customer timeline.')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, BillOfLading $record): void {
                app(BillOfLadingWorkflowService::class)->assignCustomsLane(
                    $record,
                    $data['customs_lane'],
                    auth()->id(),
                    $data['note'] ?? null,
                );
            })
            ->successNotificationTitle('Customs lane assigned');
    }
}
