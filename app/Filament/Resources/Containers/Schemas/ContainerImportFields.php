<?php

namespace App\Filament\Resources\Containers\Schemas;

use App\Models\Container;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ContainerImportFields
{
    /**
     * @return list<Component>
     */
    public static function make(): array
    {
        return [
            DateTimePicker::make('gate_out_cy_at')
                ->label('Gate out CY')
                ->seconds(false),
            Select::make('factory_loading_progress')
                ->label('Loading in Factory')
                ->options(Container::FACTORY_LOADING_PROGRESS)
                ->native(false)
                ->placeholder('Not set'),
            TextInput::make('empty_return_depot')
                ->label('Return Empty Cont in Depot')
                ->maxLength(255),
            DatePicker::make('empty_return_at')
                ->label('Return Empty Date'),
        ];
    }
}
