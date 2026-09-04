<?php

namespace App\Filament\Resources\Containers\Schemas;

use App\Filament\Infolists\LogsSection;
use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
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
                LogsSection::make(),
            ]);
    }
}
