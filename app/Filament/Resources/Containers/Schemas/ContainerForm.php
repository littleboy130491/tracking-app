<?php

namespace App\Filament\Resources\Containers\Schemas;

use App\Models\BillOfLading;
use App\Models\Container;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class ContainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Container Details')
                    ->description('Each container belongs to one bill of lading. A BL can have many containers.')
                    ->schema([
                        Select::make('bill_of_lading_id')
                            ->label('Bill of Lading')
                            ->relationship(
                                name: 'billOfLading',
                                titleAttribute: 'bl_number',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->with('company'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (BillOfLading $record): string => $record->company
                                    ? "{$record->bl_number} — {$record->company->name}"
                                    : $record->bl_number
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('container_number')
                            ->label('Container No.')
                            ->required()
                            ->maxLength(255)
                            ->rule(function (?Container $record, Get $get) {
                                return Rule::unique('containers', 'container_number')
                                    ->where(fn ($query) => $query->where('bill_of_lading_id', $get('bill_of_lading_id')))
                                    ->ignore($record);
                            }),
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
                    ->columns(2),
                Section::make('Export tracking')
                    ->description('Photos, driver, stuffing, gate in CY, VGM, and final checking follow this container.')
                    ->schema(ContainerExportFields::make())
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
