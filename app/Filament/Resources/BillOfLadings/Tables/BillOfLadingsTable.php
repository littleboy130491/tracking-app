<?php

namespace App\Filament\Resources\BillOfLadings\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillOfLadingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bl_number')
                    ->label('BL Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.company_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn ($record): string => $record->customer?->name ?? 'Not provided'),
                TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not provided'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('phase')
                    ->badge()
                    ->sortable(),
                TextColumn::make('input_date')
                    ->label('Input Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship(
                        name: 'customer',
                        titleAttribute: 'company_name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->role(User::ROLE_CUSTOMER)
                            ->orderBy('company_name'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->company_name ?? $record->name)
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->emptyStateHeading('No BL records yet')
            ->emptyStateDescription('Create a BL record and assign it to a customer.')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
