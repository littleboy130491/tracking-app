<?php

namespace App\Filament\Resources\Containers\Pages;

use App\Filament\Resources\Containers\ContainerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateContainer extends CreateRecord
{
    protected static string $resource = ContainerResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
