<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
