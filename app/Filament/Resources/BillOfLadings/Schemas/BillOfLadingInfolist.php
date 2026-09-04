<?php

namespace App\Filament\Resources\BillOfLadings\Schemas;

use App\Filament\Infolists\LogsSection;
use App\Filament\Resources\Containers\ContainerResource;
use App\Models\BillOfLading;
use App\Models\Container;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillOfLadingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Company Information')
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Company Name')
                            ->placeholder('-'),
                        TextEntry::make('company.address')
                            ->label('Company Address')
                            ->placeholder('-'),
                        TextEntry::make('shipment_type')
                            ->label('Status')
                            ->formatStateUsing(fn (?string $state): string => config("bl_workflows.shipment_types.{$state}", $state ?? '-'))
                            ->badge(),
                        TextEntry::make('shipping_method')
                            ->label('Jenis Kontainer')
                            ->formatStateUsing(fn (?string $state): string => config("bl_workflows.shipping_methods.{$state}", strtoupper($state ?? '-')))
                            ->badge(),
                    ])
                    ->columns(4),
                Section::make('Shipment Details')
                    ->schema([
                        TextEntry::make('bl_number')
                            ->label('Nomor BL'),
                        TextEntry::make('aju_number')
                            ->label('Nomor Aju')
                            ->placeholder('-'),
                        TextEntry::make('carrier_name')
                            ->label('Carrier')
                            ->placeholder('-'),
                        TextEntry::make('input_date')
                            ->label('Input Date')
                            ->date(),
                        TextEntry::make('shipped_on_board_date')
                            ->label('Shipped On Board')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('shipment_description')
                            ->label('Shipment Summary')
                            ->columnSpanFull(),
                        TextEntry::make('port_of_loading')
                            ->label('Port of Loading')
                            ->placeholder('-'),
                        TextEntry::make('port_of_discharge')
                            ->label('Port of Discharge')
                            ->placeholder('-'),
                        TextEntry::make('place_of_delivery')
                            ->label('Place of Delivery')
                            ->placeholder('-'),
                        TextEntry::make('vessel_name')
                            ->label('Vessel')
                            ->placeholder('-'),
                        TextEntry::make('voyage_number')
                            ->label('Voyage')
                            ->placeholder('-'),
                        TextEntry::make('goods_description')
                            ->label('Goods Description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('package_count')
                            ->label('Packages')
                            ->placeholder('-'),
                        TextEntry::make('gross_weight_kg')
                            ->label('Gross Weight (kg)')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('-'),
                        TextEntry::make('measurement_cbm')
                            ->label('Measurement (CBM)')
                            ->numeric(decimalPlaces: 4)
                            ->placeholder('-'),
                        TextEntry::make('hs_code')
                            ->label('HS Code')
                            ->placeholder('-'),
                        TextEntry::make('free_time_notes')
                            ->label('Free Time / Demurrage Notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Related Parties & Destination')
                    ->schema([
                        TextEntry::make('shipper_name')
                            ->label('Shipper')
                            ->placeholder('-'),
                        TextEntry::make('consignee_name')
                            ->label('Consignee')
                            ->placeholder('-'),
                        TextEntry::make('notify_party_name')
                            ->label('Notify Party')
                            ->placeholder('-'),
                        TextEntry::make('destination_agent_name')
                            ->label('Destination Agent')
                            ->placeholder('-'),
                        TextEntry::make('consignee_address')
                            ->label('Consignee Address')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Export Tracking Progress')
                    ->description('Shipment details for export Process 1–3.')
                    ->visible(fn (?BillOfLading $record): bool => $record?->isExport() ?? false)
                    ->schema([
                        TextEntry::make('exporter_name')->label('Exporter Name')->placeholder('-'),
                        IconEntry::make('booking_order_checked')->label('Checking Booking Order')->boolean(),
                        TextEntry::make('do_number')->label('No. DO')->placeholder('-'),
                        TextEntry::make('carrier_name')->label('Shipping Line')->placeholder('-'),
                        TextEntry::make('depot_closing_at')->label('Closing time at Depo')->dateTime()->placeholder('-'),
                        TextEntry::make('cy_closing_at')->label('Closing time at CY')->dateTime()->placeholder('-'),
                        TextEntry::make('container_size')->label('Size Container')->placeholder('-'),
                        TextEntry::make('pickup_depot')->label('Pick Up Depot')->placeholder('-'),
                        TextEntry::make('stuffing_date')->label('Date of Stuffing')->date()->placeholder('-'),
                        TextEntry::make('stuffing_destination')->label('Stuffing Destination')->placeholder('-'),
                        TextEntry::make('on_the_way_factory_at')->label('On The Way Factory')->dateTime()->placeholder('-'),
                        IconEntry::make('peb_npe_checked')->label('Checking PEB and NPE')->boolean(),
                        IconEntry::make('gate_in_cy_processed')->label('Process Gate In CY')->boolean(),
                        TextEntry::make('final_checking_notes')->label('Final Checking Details')->placeholder('-')->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Containers')
                    ->description('Containers for this BL. Add or edit them with the repeater on the edit form.')
                    ->schema([
                        RepeatableEntry::make('containers')
                            ->label('')
                            ->placeholder('No containers recorded.')
                            ->schema([
                                TextEntry::make('container_number')
                                    ->label('No. Container')
                                    ->url(fn ($record): ?string => $record?->getKey()
                                        ? ContainerResource::getUrl('view', ['record' => $record])
                                        : null),
                                TextEntry::make('seal_number')->label('No. Seal')->placeholder('-'),
                                TextEntry::make('container_type')->label('Type')->placeholder('-'),
                                TextEntry::make('driver_name')->label('Driver Name')->placeholder('-'),
                                TextEntry::make('license_number')->label('No. License')->placeholder('-'),
                                TextEntry::make('stuffing_progress')
                                    ->label('Stuffing')
                                    ->formatStateUsing(fn (?string $state): string => Container::STUFFING_PROGRESS[$state] ?? '-')
                                    ->placeholder('-'),
                                TextEntry::make('vgm_kg')->label('VGM (kg)')->placeholder('-'),
                                IconEntry::make('final_checked')->label('Final Checking')->boolean(),
                                TextEntry::make('package_count')->label('Packages')->placeholder('-'),
                                TextEntry::make('gross_weight_kg')->label('Weight (kg)')->placeholder('-'),
                                TextEntry::make('measurement_cbm')->label('CBM')->placeholder('-'),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Workflow Milestones')
                    ->schema([
                        RepeatableEntry::make('milestoneStates')
                            ->label('')
                            ->placeholder('No milestones yet.')
                            ->schema([
                                TextEntry::make('sequence')->label('#'),
                                TextEntry::make('label')->label('Milestone'),
                                TextEntry::make('state')
                                    ->label('State')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => config("bl_workflows.milestone_states.{$state}", $state ?? '-')),
                                TextEntry::make('completed_at')
                                    ->label('Completed')
                                    ->dateTime()
                                    ->placeholder('-'),
                            ])
                            ->columns(4),
                    ]),
                Section::make('Current Tracking')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('phase')
                            ->label('Phase / Milestone')
                            ->badge(),
                        TextEntry::make('customs_lane')
                            ->label('Customs Lane')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? config("bl_workflows.customs_lanes.{$state}") : null)
                            ->placeholder('Not assigned')
                            ->badge(),
                        TextEntry::make('gps_tracking_url')
                            ->label('Tracking URL')
                            ->placeholder('-')
                            ->url(fn ($state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('updated_at')
                            ->label('Last Update')
                            ->dateTime(),
                        TextEntry::make('customer_note')
                            ->label('Customer Note')
                            ->placeholder('No customer note'),
                    ])
                    ->columns(2),
                Section::make('Update History')
                    ->schema([
                        RepeatableEntry::make('updates')
                            ->label('')
                            ->placeholder('No update history yet.')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Updated')
                                    ->dateTime(),
                                TextEntry::make('user.name')
                                    ->label('Updated By')
                                    ->placeholder('System'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                                TextEntry::make('phase')
                                    ->label('Phase')
                                    ->badge(),
                                TextEntry::make('milestone_key')
                                    ->label('Milestone')
                                    ->placeholder('-'),
                                TextEntry::make('visibility')
                                    ->label('Visibility')
                                    ->badge(),
                                TextEntry::make('note')
                                    ->label('Note')
                                    ->placeholder('No note')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
                LogsSection::make(),
            ]);
    }
}
