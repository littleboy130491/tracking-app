<?php

namespace App\Filament\Resources\BillOfLadings\Actions;

use App\Models\BillOfLading;
use App\Services\BillOfLadingWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class ActivateDeliveryTrackAction
{
    public static function make(): Action
    {
        return Action::make('activateDeliveryTrack')
            ->label('Activate Delivery Track')
            ->icon(Heroicon::OutlinedTruck)
            ->color('gray')
            ->visible(fn (BillOfLading $record): bool => $record->milestoneStates()
                ->where('workflow_key', 'delivery')
                ->doesntExist()
                && (auth()->user()?->canManageMilestone('finalize_docs') ?? false))
            ->modalHeading('Activate Delivery Track')
            ->modalDescription('Append the pengiriman / delivery milestones to this BL.')
            ->modalSubmitActionLabel('Activate')
            ->schema([
                Textarea::make('note')
                    ->label('Note')
                    ->placeholder('Optional note when delivery starts.')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, BillOfLading $record): void {
                app(BillOfLadingWorkflowService::class)->activateDeliveryTrack(
                    $record,
                    auth()->id(),
                    $data['note'] ?? null,
                );
            })
            ->successNotificationTitle('Delivery track activated');
    }
}
