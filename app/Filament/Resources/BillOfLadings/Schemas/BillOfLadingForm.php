<?php

namespace App\Filament\Resources\BillOfLadings\Schemas;

use App\Models\BillOfLading;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Section::make('Assignment')
                    ->schema([
                        TextInput::make('bl_number')
                            ->label('BL Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('booking_number')
                            ->label('Booking Number')
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
                        Select::make('shipment_type')
                            ->label('Shipment Type')
                            ->options(config('bl_workflows.shipment_types'))
                            ->default(BillOfLading::TYPE_IMPORT)
                            ->required()
                            ->live(),
                        DatePicker::make('input_date')
                            ->label('Input Date')
                            ->default(now())
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Carrier & Document')
                    ->schema([
                        TextInput::make('carrier_name')
                            ->label('Carrier')
                            ->maxLength(255),
                        Select::make('bl_document_type')
                            ->label('BL Document Type')
                            ->options(BillOfLading::DOCUMENT_TYPES),
                        Toggle::make('bl_surrendered')
                            ->label('BL Surrendered')
                            ->inline(false),
                        DatePicker::make('issue_date')
                            ->label('Issue Date'),
                        TextInput::make('place_of_issue')
                            ->label('Place of Issue')
                            ->maxLength(255),
                        DatePicker::make('shipped_on_board_date')
                            ->label('Shipped On Board Date'),
                        TextInput::make('export_reference')
                            ->label('Export / Service Reference')
                            ->maxLength(255),
                        TextInput::make('freight_terms')
                            ->label('Freight Terms')
                            ->placeholder('FREIGHT PREPAID')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Parties')
                    ->schema([
                        TextInput::make('shipper_name')
                            ->label('Shipper')
                            ->maxLength(255),
                        Textarea::make('shipper_address')
                            ->label('Shipper Address')
                            ->rows(2),
                        TextInput::make('consignee_name')
                            ->label('Consignee')
                            ->maxLength(255),
                        Textarea::make('consignee_address')
                            ->label('Consignee Address')
                            ->rows(2),
                        TextInput::make('consignee_npwp')
                            ->label('Consignee NPWP')
                            ->maxLength(255),
                        TextInput::make('notify_party_name')
                            ->label('Notify Party')
                            ->maxLength(255),
                        Textarea::make('notify_party_address')
                            ->label('Notify Party Address')
                            ->rows(2),
                        TextInput::make('destination_agent_name')
                            ->label('Destination Agent')
                            ->maxLength(255),
                        Textarea::make('destination_agent_contact')
                            ->label('Destination Agent Contact')
                            ->rows(2)
                            ->helperText('Admin-only by default.'),
                    ])
                    ->columns(2),
                Section::make('Routing')
                    ->schema([
                        TextInput::make('place_of_receipt')
                            ->label('Place of Receipt')
                            ->maxLength(255),
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
                        TextInput::make('movement_type')
                            ->label('Movement Type')
                            ->placeholder('CY-CY / FCL')
                            ->maxLength(255),
                        TextInput::make('service_type')
                            ->label('Service Type')
                            ->maxLength(255),
                        TextInput::make('origin')
                            ->label('Origin (legacy)')
                            ->helperText('Synced from Port of Loading when empty.')
                            ->maxLength(255)
                            ->dehydrated(),
                        TextInput::make('destination')
                            ->label('Destination (legacy)')
                            ->helperText('Synced from Port of Discharge when empty.')
                            ->maxLength(255)
                            ->dehydrated(),
                    ])
                    ->columns(2),
                Section::make('Cargo Summary')
                    ->schema([
                        Textarea::make('shipment_description')
                            ->label('Shipment Summary')
                            ->helperText('Short overview shown in lists and the page header.')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('goods_description')
                            ->label('Goods Description')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('items_description')
                            ->label('Items Information (legacy)')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('hs_code')
                            ->label('HS Code')
                            ->maxLength(255),
                        TextInput::make('package_count')
                            ->label('Package Count')
                            ->placeholder('2560 BAGS')
                            ->maxLength(255),
                        TextInput::make('quantity')
                            ->label('Quantity (legacy)')
                            ->maxLength(255),
                        TextInput::make('container_count_label')
                            ->label('Container Count Label')
                            ->placeholder("4 x 40' HIGH CUBE")
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
                        TextInput::make('volume_cbm')
                            ->label('Volume CBM (legacy)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        Textarea::make('marks_and_numbers')
                            ->label('Marks & Numbers')
                            ->rows(2),
                        Textarea::make('free_time_notes')
                            ->label('Free Time / Demurrage Notes')
                            ->rows(2),
                    ])
                    ->columns(2),
                Section::make('Containers')
                    ->schema([
                        Repeater::make('containers')
                            ->relationship()
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
                                TextInput::make('tare_weight_kg')
                                    ->label('Tare (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.001),
                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['container_number'] ?? null)
                            ->addActionLabel('Add container')
                            ->columnSpanFull(),
                    ]),
                Section::make('Current Progress')
                    ->description('Use Advance Milestone / Assign Customs Lane on the BL view page for workflow updates. Phase is derived from the active milestone.')
                    ->schema([
                        Select::make('status')
                            ->options(array_combine(BillOfLading::STATUSES, BillOfLading::STATUSES))
                            ->default(BillOfLading::STATUS_PENDING)
                            ->required(),
                        TextInput::make('phase')
                            ->label('Phase / Milestone')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('customs_lane')
                            ->label('Customs Lane')
                            ->options(config('bl_workflows.customs_lanes'))
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Assign lane from the BL view action after PIB response.'),
                        TextInput::make('current_milestone_key')
                            ->label('Current Milestone Key')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('gps_tracking_url')
                            ->label('GPS Tracking URL')
                            ->url()
                            ->maxLength(2048),
                        Textarea::make('customer_note')
                            ->label('Customer-visible note')
                            ->rows(2),
                        Textarea::make('internal_note')
                            ->label('Internal note (admin only)')
                            ->rows(2),
                        Textarea::make('note')
                            ->label('Latest update note (legacy)')
                            ->helperText('Kept in sync with customer-visible note when possible.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
