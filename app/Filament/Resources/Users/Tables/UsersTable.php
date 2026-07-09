<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pic_name')
                    ->label('PIC Name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('bill_of_ladings_count')
                    ->label('BL Records')
                    ->counts('billOfLadings')
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('company_name')
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Create a customer account to start assigning BL records.')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
