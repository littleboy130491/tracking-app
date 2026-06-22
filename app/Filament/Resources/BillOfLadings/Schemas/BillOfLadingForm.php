<?php

namespace App\Filament\Resources\BillOfLadings\Schemas;

use App\Models\BillOfLading;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BillOfLadingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment')
                    ->schema([
                        TextInput::make('bl_number')
                            ->label('BL Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('customer_id')
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
                            ->preload()
                            ->required(),
                        DatePicker::make('input_date')
                            ->label('Input Date')
                            ->default(now())
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Shipment Details')
                    ->schema([
                        Textarea::make('shipment_description')
                            ->label('Shipment Summary')
                            ->helperText('Short overview shown in lists and the page header.')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('origin')
                            ->label('Origin')
                            ->maxLength(255),
                        TextInput::make('destination')
                            ->label('Destination')
                            ->maxLength(255),
                        Textarea::make('items_description')
                            ->label('Items Information')
                            ->helperText('Describe the goods, packaging, HS codes, or other cargo details.')
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->helperText('Example: 120 cartons, 2 x 40ft containers')
                            ->maxLength(255),
                        TextInput::make('gross_weight_kg')
                            ->label('Gross Weight (kg)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        TextInput::make('volume_cbm')
                            ->label('Volume (CBM)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                    ])
                    ->columns(2),
                Section::make('Current Progress')
                    ->description('To log a progress update without editing shipment details, use Post Progress Update on the BL view page.')
                    ->schema([
                        Select::make('status')
                            ->options(array_combine(BillOfLading::STATUSES, BillOfLading::STATUSES))
                            ->default('Pending')
                            ->required(),
                        Select::make('phase')
                            ->options(array_combine(BillOfLading::PHASES, BillOfLading::PHASES))
                            ->default('Input')
                            ->required(),
                        TextInput::make('gps_tracking_url')
                            ->label('GPS Tracking URL')
                            ->url()
                            ->maxLength(2048),
                        Textarea::make('note')
                            ->label('Latest update note')
                            ->helperText('Saved to update history when status, phase, or this note changes.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
