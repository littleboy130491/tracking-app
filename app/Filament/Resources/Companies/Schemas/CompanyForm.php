<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Company Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Company Address')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('users')
                            ->label('Customer users')
                            ->relationship(
                                name: 'users',
                                titleAttribute: 'email',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->role(User::ROLE_CUSTOMER)
                                    ->orderBy('email'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (User $record): string => $record->pic_name
                                    ? "{$record->pic_name} ({$record->email})"
                                    : $record->email
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Users assigned here can access every BL for this company.'),
                    ])
                    ->columns(1),
            ]);
    }
}
