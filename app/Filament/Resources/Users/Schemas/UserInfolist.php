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
                        TextEntry::make('company_name')
                            ->label('Company Name')
                            ->placeholder('-'),
                        TextEntry::make('company_address')
                            ->label('Company Address')
                            ->placeholder('-'),
                        TextEntry::make('pic_name')
                            ->label('PIC Name')
                            ->placeholder('-'),
                        TextEntry::make('pic_phone')
                            ->label('PIC Phone Number')
                            ->placeholder('-'),
                        TextEntry::make('bill_of_ladings_count')
                            ->label('BL Records')
                            ->state(fn ($record): int => $record->billOfLadings()->count()),
                        TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime()
                            ->placeholder('Never logged in'),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])
                    ->columns(1),
            ]);
    }
}
