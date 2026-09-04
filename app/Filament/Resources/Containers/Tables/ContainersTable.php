<?php

namespace App\Filament\Resources\Containers\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContainersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('container_number')
                    ->label('Container No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('billOfLading.bl_number')
                    ->label('BL Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('billOfLading.company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('seal_number')
                    ->label('Seal')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('container_type')
                    ->label('Type')
                    ->placeholder('-'),
                TextColumn::make('package_count')
                    ->label('Packages')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('gross_weight_kg')
                    ->label('Weight (kg)')
                    ->numeric(decimalPlaces: 3)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('bill_of_lading_id')
                    ->label('Bill of Lading')
                    ->relationship('billOfLading', 'bl_number')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('container_number')
            ->emptyStateIcon(Heroicon::OutlinedCube)
            ->emptyStateHeading('No containers yet')
            ->emptyStateDescription('Add containers on a BL record, or create one here and assign it to a bill of lading.')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
