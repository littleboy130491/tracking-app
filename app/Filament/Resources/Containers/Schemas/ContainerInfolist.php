<?php

namespace App\Filament\Resources\Containers\Schemas;

use App\Filament\Infolists\LogsSection;
use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use App\Models\Container;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContainerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Container')
                    ->schema([
                        TextEntry::make('container_number')
                            ->label('Container No.'),
                        TextEntry::make('billOfLading.bl_number')
                            ->label('Bill of Lading')
                            ->url(fn ($record): ?string => $record->bill_of_lading_id
                                ? BillOfLadingResource::getUrl('view', ['record' => $record->bill_of_lading_id])
                                : null),
                        TextEntry::make('billOfLading.company.name')
                            ->label('Company')
                            ->placeholder('-'),
                        TextEntry::make('seal_number')
                            ->label('Seal No.')
                            ->placeholder('-'),
                        TextEntry::make('container_type')
                            ->label('Type')
                            ->placeholder('-'),
                        TextEntry::make('package_count')
                            ->label('Packages')
                            ->placeholder('-'),
                        TextEntry::make('gross_weight_kg')
                            ->label('Gross Weight (kg)')
                            ->placeholder('-'),
                        TextEntry::make('measurement_cbm')
                            ->label('CBM')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Export tracking')
                    ->schema([
                        ImageEntry::make('photo_door_path')->label('Pintu')->disk('public')->placeholder('-'),
                        ImageEntry::make('photo_floor_path')->label('Lantai')->disk('public')->placeholder('-'),
                        ImageEntry::make('photo_eir_path')->label('EIR')->disk('public')->placeholder('-'),
                        ImageEntry::make('photo_seal_path')->label('Seal')->disk('public')->placeholder('-'),
                        TextEntry::make('driver_name')->label('Driver Name')->placeholder('-'),
                        TextEntry::make('license_number')->label('No. License')->placeholder('-'),
                        TextEntry::make('driver_tracking_url')
                            ->label('Tracking Position Driver')
                            ->placeholder('-')
                            ->url(fn ($state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('stuffing_progress')
                            ->label('Progress Stuffing at Factory')
                            ->formatStateUsing(fn (?string $state): string => Container::STUFFING_PROGRESS[$state] ?? '-')
                            ->placeholder('-'),
                        TextEntry::make('gate_in_pol')->label('Gate in CY — POL')->placeholder('-'),
                        TextEntry::make('gate_in_cy_at')->label('Gate in CY Date')->dateTime()->placeholder('-'),
                        TextEntry::make('vgm_kg')->label('Amount of VGM (kg)')->placeholder('-'),
                        IconEntry::make('final_checked')->label('Final Checking')->boolean(),
                        TextEntry::make('final_checked_at')->label('Final Checking Date')->date()->placeholder('-'),
                    ])
                    ->columns(4),
                LogsSection::make(),
            ]);
    }
}
