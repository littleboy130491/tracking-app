<?php

namespace App\Filament\Resources\BillOfLadings\Actions;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
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
            ->visible(fn (BillOfLading $record): bool => auth()->user()?->canManageMilestone($record->current_milestone_key) ?? false)
            ->modalHeading('Post Progress Update')
            ->modalDescription('Add a free-form status note without advancing the milestone engine. Prefer Advance Milestone for operational steps.')
            ->modalSubmitActionLabel('Post update')
            ->schema([
                Select::make('status')
                    ->options(array_combine(BillOfLading::STATUSES, BillOfLading::STATUSES))
                    ->required(),
                Select::make('visibility')
                    ->label('Visibility')
                    ->options(config('bl_workflows.visibilities'))
                    ->default(BillOfLadingUpdate::VISIBILITY_CUSTOMER)
                    ->required(),
                Textarea::make('note')
                    ->label('Update note')
                    ->placeholder('Describe what changed in this progress update.')
                    ->required()
                    ->columnSpanFull(),
            ])
            ->fillForm(fn (BillOfLading $record): array => [
                'status' => $record->status,
                'visibility' => BillOfLadingUpdate::VISIBILITY_CUSTOMER,
                'note' => '',
            ])
            ->action(function (array $data, BillOfLading $record): void {
                $record->postProgressUpdate($data, auth()->id());
            })
            ->successNotificationTitle('Progress update posted');
    }
}
