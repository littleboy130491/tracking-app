<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Company Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Company Name'),
                        TextEntry::make('address')
                            ->label('Company Address')
                            ->placeholder('-'),
                        TextEntry::make('users.email')
                            ->label('Customer Users')
                            ->badge()
                            ->placeholder('No users assigned'),
                        TextEntry::make('bill_of_ladings_count')
                            ->label('BL Records')
                            ->state(fn ($record): int => $record->billOfLadings()->count()),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])
                    ->columns(1),
            ]);
    }
}
