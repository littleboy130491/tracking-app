<?php

namespace App\Filament\Resources\Containers\Schemas;

use App\Models\Container;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\Enums\MimeType;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
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
            self::documentationPhoto('photo_door_id', 'Photo of Door (Pintu)'),
            self::documentationPhoto('photo_floor_id', 'Photo of Floor (Lantai)'),
            self::documentationPhoto('photo_eir_id', 'Photo of EIR'),
            self::documentationPhoto('photo_seal_id', 'Photo of Seal'),
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

    private static function documentationPhoto(string $field, string $label): CuratorPicker
    {
        return CuratorPicker::make($field)
            ->label($label)
            ->acceptedFileTypes([
                MimeType::ImageJpeg->value,
                MimeType::ImagePng->value,
                MimeType::ImageWebp->value,
                MimeType::ImageGif->value,
            ])
            ->disk('public')
            ->directory('container-photos')
            ->visibility('public')
            ->constrained();
    }
}
