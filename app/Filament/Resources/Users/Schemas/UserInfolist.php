<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Customer Information')
                    ->schema([
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('companies.name')
                            ->label('Companies')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('pic_name')
                            ->label('PIC Name')
                            ->placeholder('-'),
                        TextEntry::make('pic_phone')
                            ->label('PIC Phone Number')
                            ->placeholder('-'),
                        TextEntry::make('bill_of_ladings_count')
                            ->label('BL Records')
                            ->state(fn ($record): int => $record->accessibleBillOfLadings()->count()),
                        TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime()
                            ->placeholder('Never logged in'),
                        TextEntry::make('is_active')
                            ->label('Account Status')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])
                    ->columns(1),
            ]);
    }
}
