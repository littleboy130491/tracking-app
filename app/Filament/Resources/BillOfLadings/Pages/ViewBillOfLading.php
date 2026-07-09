<?php

namespace App\Filament\Resources\BillOfLadings\Pages;

use App\Filament\Resources\BillOfLadings\Actions\ActivateDeliveryTrackAction;
use App\Filament\Resources\BillOfLadings\Actions\AdvanceMilestoneAction;
use App\Filament\Resources\BillOfLadings\Actions\AssignCustomsLaneAction;
use App\Filament\Resources\BillOfLadings\Actions\PostProgressUpdateAction;
use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewBillOfLading extends ViewRecord
{
    protected static string $resource = BillOfLadingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            AdvanceMilestoneAction::make(),
            AssignCustomsLaneAction::make(),
            ActivateDeliveryTrackAction::make(),
            PostProgressUpdateAction::make(),
            EditAction::make(),
        ];
    }
}
