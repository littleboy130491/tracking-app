<?php

namespace App\Filament\Resources\BillOfLadings\Schemas;

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
                Section::make('Customer Information')
                    ->schema([
                        TextEntry::make('customer.company_name')
                            ->label('Customer Name')
                            ->placeholder('-'),
                        TextEntry::make('customer.email')
                            ->label('Email'),
                        TextEntry::make('shipment_type')
                            ->label('Shipment Type')
                            ->formatStateUsing(fn (?string $state): string => config("bl_workflows.shipment_types.{$state}", $state ?? '-'))
                            ->badge(),
                    ])
                    ->columns(3),
                Section::make('Shipment Details')
                    ->schema([
                        TextEntry::make('bl_number')
                            ->label('BL Number'),
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
                Section::make('Containers')
                    ->schema([
                        RepeatableEntry::make('containers')
                            ->label('')
                            ->placeholder('No containers recorded.')
                            ->schema([
                                TextEntry::make('container_number')->label('Container'),
                                TextEntry::make('seal_number')->label('Seal')->placeholder('-'),
                                TextEntry::make('container_type')->label('Type')->placeholder('-'),
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
                            ->label('GPS Tracking URL')
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
                Section::make('Audit Log')
                    ->schema([
                        RepeatableEntry::make('audits')
                            ->label('')
                            ->placeholder('No audited changes yet.')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Changed')
                                    ->dateTime(),
                                TextEntry::make('user.name')
                                    ->label('Changed By')
                                    ->placeholder('System'),
                                TextEntry::make('event')
                                    ->label('Event')
                                    ->badge(),
                                TextEntry::make('changes')
                                    ->label('Changes')
                                    ->formatStateUsing(fn ($state): string => collect($state ?? [])
                                        ->keys()
                                        ->join(', '))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ])
                    ->collapsed(),
            ]);
    }
}
