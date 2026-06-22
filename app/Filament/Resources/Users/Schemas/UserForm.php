<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Customer Details')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),
                        Textarea::make('company_address')
                            ->label('Company Address')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('pic_name')
                            ->label('PIC Name')
                            ->maxLength(255),
                        TextInput::make('pic_phone')
                            ->label('PIC Phone Number')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(1),
            ]);
    }
}
