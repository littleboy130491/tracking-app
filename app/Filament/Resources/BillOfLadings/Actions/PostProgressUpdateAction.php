<?php

namespace App\Filament\Resources\BillOfLadings\Actions;

use App\Models\BillOfLading;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class PostProgressUpdateAction
{
    public static function make(): Action
    {
        return Action::make('postProgressUpdate')
            ->label('Post Progress Update')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->modalHeading('Post Progress Update')
            ->modalDescription('Add a new status, phase, and note for this BL. Customers will see it in their update history.')
            ->modalSubmitActionLabel('Post update')
            ->schema([
                Select::make('status')
                    ->options(array_combine(BillOfLading::STATUSES, BillOfLading::STATUSES))
                    ->required(),
                Select::make('phase')
                    ->options(array_combine(BillOfLading::PHASES, BillOfLading::PHASES))
                    ->required(),
                Textarea::make('note')
                    ->label('Update note')
                    ->placeholder('Describe what changed in this progress update.')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->fillForm(fn (BillOfLading $record): array => [
                'status' => $record->status,
                'phase' => $record->phase,
                'note' => '',
            ])
            ->action(function (array $data, BillOfLading $record): void {
                $record->postProgressUpdate($data);
            })
            ->successNotificationTitle('Progress update posted');
    }
}
