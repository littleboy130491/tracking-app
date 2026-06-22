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
                            ->placeholder('Not provided'),
                        TextEntry::make('customer.email')
                            ->label('Email'),
                    ])
                    ->columns(1),
                Section::make('Shipment Details')
                    ->schema([
                        TextEntry::make('bl_number')
                            ->label('BL Number'),
                        TextEntry::make('input_date')
                            ->label('Input Date')
                            ->date(),
                        TextEntry::make('shipment_description')
                            ->label('Shipment Summary'),
                        TextEntry::make('origin')
                            ->label('Origin')
                            ->placeholder('Not provided'),
                        TextEntry::make('destination')
                            ->label('Destination')
                            ->placeholder('Not provided'),
                        TextEntry::make('items_description')
                            ->label('Items Information')
                            ->placeholder('Not provided'),
                        TextEntry::make('quantity')
                            ->label('Quantity')
                            ->placeholder('Not provided'),
                        TextEntry::make('gross_weight_kg')
                            ->label('Gross Weight (kg)')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('Not provided'),
                        TextEntry::make('volume_cbm')
                            ->label('Volume (CBM)')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder('Not provided'),
                    ])
                    ->columns(1),
                Section::make('Current Tracking')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('phase')
                            ->label('Phase')
                            ->badge(),
                        TextEntry::make('gps_tracking_url')
                            ->label('GPS Tracking URL')
                            ->placeholder('Not provided')
                            ->url(fn ($state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('updated_at')
                            ->label('Last Update')
                            ->dateTime(),
                        TextEntry::make('note')
                            ->label('Current Note')
                            ->placeholder('No current note'),
                    ])
                    ->columns(1),
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
                                TextEntry::make('note')
                                    ->label('Note')
                                    ->placeholder('No note'),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }
}
