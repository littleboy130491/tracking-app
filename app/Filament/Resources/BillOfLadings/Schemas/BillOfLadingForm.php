<?php

namespace App\Filament\Resources\BillOfLadings\Schemas;

use App\Models\BillOfLading;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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
            ->columns(1)
            ->components([
                Section::make('Process Information')
                    ->description('Choose the document process and assign it to a customer.')
                    ->schema([
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
                        Select::make('shipment_type')
                            ->label('Shipment Type')
                            ->options(config('bl_workflows.shipment_types'))
                            ->default(BillOfLading::TYPE_IMPORT)
                            ->required()
                            ->disabled(fn (?BillOfLading $record): bool => $record !== null)
                            ->dehydrated(fn (?BillOfLading $record): bool => $record === null)
                            ->live(),
                        Select::make('shipping_method')
                            ->label('Shipping Method')
                            ->options(config('bl_workflows.shipping_methods'))
                            ->default(BillOfLading::SHIPPING_METHOD_FCL)
                            ->required()
                            ->native(false),
                        DatePicker::make('input_date')
                            ->label('Input Date')
                            ->default(now())
                            ->required(),
                        TextInput::make('bl_number')
                            ->label('BL Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('BL & Route Details')
                    ->description('BL summary, carrier, route, and schedule shown to the customer.')
                    ->schema([
                        Textarea::make('shipment_description')
                            ->label('Shipment Summary')
                            ->helperText('Short overview shown in lists and the customer page header.')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('carrier_name')
                            ->label('Carrier')
                            ->maxLength(255),
                        DatePicker::make('shipped_on_board_date')
                            ->label('Shipped On Board Date'),
                        TextInput::make('port_of_loading')
                            ->label('Port of Loading')
                            ->maxLength(255),
                        TextInput::make('port_of_discharge')
                            ->label('Port of Discharge')
                            ->maxLength(255),
                        TextInput::make('place_of_delivery')
                            ->label('Place of Delivery')
                            ->maxLength(255),
                        TextInput::make('vessel_name')
                            ->label('Vessel')
                            ->maxLength(255),
                        TextInput::make('voyage_number')
                            ->label('Voyage No.')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Cargo & Containers')
                    ->description('Cargo totals and container details shown to the customer.')
                    ->schema([
                        Textarea::make('goods_description')
                            ->label('Goods Description')
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('hs_code')
                            ->label('HS Code')
                            ->maxLength(255),
                        TextInput::make('package_count')
                            ->label('Package Count')
                            ->placeholder('2560 BAGS')
                            ->maxLength(255),
                        TextInput::make('gross_weight_kg')
                            ->label('Gross Weight (kg)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        TextInput::make('measurement_cbm')
                            ->label('Measurement (CBM)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001),
                        Textarea::make('free_time_notes')
                            ->label('Free Time / Demurrage Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                        Repeater::make('containers')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->schema([
                                TextInput::make('container_number')
                                    ->label('Container No.')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('seal_number')
                                    ->label('Seal No.')
                                    ->maxLength(255),
                                TextInput::make('container_type')
                                    ->label('Type')
                                    ->placeholder("40'HC")
                                    ->maxLength(255),
                                TextInput::make('package_count')
                                    ->label('Packages')
                                    ->maxLength(255),
                                TextInput::make('gross_weight_kg')
                                    ->label('Gross Weight (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.001),
                                TextInput::make('measurement_cbm')
                                    ->label('CBM')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.0001),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['container_number'] ?? null)
                            ->addActionLabel('Add container')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Parties & Customer Update')
                    ->description('Additional customer-facing details. Status and milestones remain managed through workflow actions.')
                    ->schema([
                        TextInput::make('shipper_name')
                            ->label('Shipper')
                            ->maxLength(255),
                        TextInput::make('consignee_name')
                            ->label('Consignee')
                            ->maxLength(255),
                        Textarea::make('consignee_address')
                            ->label('Consignee Address')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('notify_party_name')
                            ->label('Notify Party')
                            ->maxLength(255),
                        TextInput::make('destination_agent_name')
                            ->label('Destination Agent')
                            ->maxLength(255),
                        TextInput::make('gps_tracking_url')
                            ->label('GPS Tracking URL')
                            ->url()
                            ->maxLength(2048),
                        Textarea::make('customer_note')
                            ->label('Customer-visible Note')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
