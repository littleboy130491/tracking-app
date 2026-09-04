<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                        Select::make('companies')
                            ->label('Companies')
                            ->relationship('companies', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Company Name')
                                    ->required()
                                    ->unique('companies', 'name')
                                    ->maxLength(255),
                                Textarea::make('address')
                                    ->label('Company Address')
                                    ->rows(3),
                            ]),
                        TextInput::make('pic_name')
                            ->label('PIC Name')
                            ->maxLength(255),
                        TextInput::make('pic_phone')
                            ->label('PIC Phone Number')
                            ->tel()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Account active')
                            ->default(true),
                    ])
                    ->columns(1),
            ]);
    }
}
