<?php

namespace App\Filament\Resources\BillOfLadings\Pages;

use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use App\Filament\Resources\BillOfLadings\Actions\PostProgressUpdateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBillOfLading extends ViewRecord
{
    protected static string $resource = BillOfLadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PostProgressUpdateAction::make(),
            EditAction::make(),
        ];
    }
}
