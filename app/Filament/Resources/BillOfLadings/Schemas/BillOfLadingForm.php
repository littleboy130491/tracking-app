<?php

namespace App\Filament\Resources\BillOfLadings\Schemas;

use App\Filament\Resources\Containers\Schemas\ContainerExportFields;
use App\Models\BillOfLading;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BillOfLadingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Process Information')
                    ->description('Choose the document process and assign it to a company.')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->relationship(
                                name: 'company',
                                titleAttribute: 'name',
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?int $state, Set $set, string $operation): void {
                                if ($operation !== 'create' || blank($state)) {
                                    return;
                                }

                                $company = Company::query()->find($state);

                                if (! $company) {
                                    return;
                                }

                                $set('consignee_name', $company->name);
                                $set('consignee_address', $company->address);
                                $set('exporter_name', $company->name);
                                $set('shipper_name', $company->name);
                            }),
                        Select::make('shipment_type')
                            ->label('Status')
                            ->helperText('Import or export. This determines the next tracking steps.')
                            ->options(config('bl_workflows.shipment_types'))
                            ->default(BillOfLading::TYPE_IMPORT)
                            ->required()
                            ->disabled(fn (?BillOfLading $record): bool => $record !== null)
                            ->dehydrated(fn (?BillOfLading $record): bool => $record === null)
                            ->live(),
                        Select::make('shipping_method')
                            ->label('Jenis Kontainer')
                            ->options(config('bl_workflows.shipping_methods'))
                            ->default(BillOfLading::SHIPPING_METHOD_FCL)
                            ->required()
                            ->native(false),
                        DatePicker::make('input_date')
                            ->label('Input Date')
                            ->default(now())
                            ->required(),
                        TextInput::make('bl_number')
                            ->label('Nomor BL')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('aju_number')
                            ->label('Nomor Aju')
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
                            ->label('Container list')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->schema([
                                TextInput::make('container_number')
                                    ->label('No. Container')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('seal_number')
                                    ->label('No. Seal')
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
                                ...ContainerExportFields::make(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['container_number'] ?? null)
                            ->addActionLabel('Add container')
                            ->helperText('One BL can have many containers. Driver, photos, VGM, and final checking follow each container.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Export Process 1 — OUTPUT')
                    ->description('Document Received. These shipment details are shown on the customer export tracking page.')
                    ->visible(fn (Get $get, ?BillOfLading $record): bool => self::isExport($get, $record))
                    ->schema([
                        TextInput::make('exporter_name')
                            ->label('Exporter Name (customer)')
                            ->maxLength(255),
                        Toggle::make('booking_order_checked')
                            ->label('Checking Booking Order'),
                        TextInput::make('do_number')
                            ->label('No. DO')
                            ->maxLength(255),
                        TextInput::make('carrier_name')
                            ->label('Shipping Line')
                            ->maxLength(255),
                        DateTimePicker::make('depot_closing_at')
                            ->label('Closing time at Depo')
                            ->seconds(false),
                        DateTimePicker::make('cy_closing_at')
                            ->label('Closing time at CY (pelabuhan)')
                            ->seconds(false),
                        TextInput::make('vessel_name')
                            ->label('Vessel Name')
                            ->maxLength(255),
                        TextInput::make('voyage_number')
                            ->label('Voyage')
                            ->maxLength(255),
                        TextInput::make('container_size')
                            ->label('Size Container')
                            ->placeholder("1x40'HC")
                            ->maxLength(255),
                        TextInput::make('port_of_discharge')
                            ->label('Port of Discharging')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Export Process 2 — INPUT')
                    ->description('Pick up empty container at depot and fill container documentation. Photos, driver, seal, and license follow each container in the repeater.')
                    ->visible(fn (Get $get, ?BillOfLading $record): bool => self::isExport($get, $record))
                    ->schema([
                        TextInput::make('pickup_depot')
                            ->label('Pick Up Depot')
                            ->maxLength(255),
                        DatePicker::make('stuffing_date')
                            ->label('Date of Stuffing'),
                        TextInput::make('stuffing_destination')
                            ->label('Stuffing Destination')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Export Process 3 — FINAL')
                    ->description('Factory stuffing, PEB/NPE, gate in CY, and final checking.')
                    ->visible(fn (Get $get, ?BillOfLading $record): bool => self::isExport($get, $record))
                    ->schema([
                        DateTimePicker::make('on_the_way_factory_at')
                            ->label('Container On The Way Factory')
                            ->seconds(false),
                        Toggle::make('peb_npe_checked')
                            ->label('Checking PEB and NPE'),
                        Toggle::make('gate_in_cy_processed')
                            ->label('Process Gate In CY'),
                        Textarea::make('final_checking_notes')
                            ->label('Final Checking Details Shipment')
                            ->rows(3)
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
                            ->label('Tracking URL')
                            ->helperText('External tracking link shown to the customer.')
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

    private static function isExport(Get $get, ?BillOfLading $record): bool
    {
        return ($get('shipment_type') ?? $record?->shipment_type) === BillOfLading::TYPE_EXPORT;
    }
}
