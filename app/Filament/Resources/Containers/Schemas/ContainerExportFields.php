<?php

namespace App\Filament\Resources\Containers\Schemas;

use App\Models\Container;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ContainerExportFields
{
    /**
     * @return list<Component>
     */
    public static function make(): array
    {
        return [
            FileUpload::make('photo_door_path')
                ->label('Photo of Door (Pintu)')
                ->image()
                ->disk('public')
                ->directory('container-photos')
                ->visibility('public'),
            FileUpload::make('photo_floor_path')
                ->label('Photo of Floor (Lantai)')
                ->image()
                ->disk('public')
                ->directory('container-photos')
                ->visibility('public'),
            FileUpload::make('photo_eir_path')
                ->label('Photo of EIR')
                ->image()
                ->disk('public')
                ->directory('container-photos')
                ->visibility('public'),
            FileUpload::make('photo_seal_path')
                ->label('Photo of Seal')
                ->image()
                ->disk('public')
                ->directory('container-photos')
                ->visibility('public'),
            TextInput::make('driver_name')
                ->label('Driver Name')
                ->maxLength(255),
            TextInput::make('license_number')
                ->label('No. License')
                ->maxLength(255),
            TextInput::make('driver_tracking_url')
                ->label('Tracking Position Driver')
                ->url()
                ->maxLength(2048),
            Select::make('stuffing_progress')
                ->label('Progress Stuffing at Factory')
                ->options(Container::STUFFING_PROGRESS)
                ->native(false)
                ->placeholder('Not set'),
            TextInput::make('gate_in_pol')
                ->label('Gate in CY — Port of Loading')
                ->maxLength(255),
            DateTimePicker::make('gate_in_cy_at')
                ->label('Gate in CY Date')
                ->seconds(false),
            TextInput::make('vgm_kg')
                ->label('Amount of VGM (kg)')
                ->numeric()
                ->minValue(0)
                ->step(0.001),
            Toggle::make('final_checked')
                ->label('Final Checking (sudah dicek)'),
            DatePicker::make('final_checked_at')
                ->label('Final Checking Date'),
        ];
    }
}
