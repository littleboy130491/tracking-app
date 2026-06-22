<?php

namespace App\Filament\Resources\BillOfLadings\Pages;

use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use App\Models\BillOfLading;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class CreateBillOfLading extends CreateRecord
{
    protected static string $resource = BillOfLadingResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function afterCreate(): void
    {
        /** @var BillOfLading $record */
        $record = $this->record;

        $record->updates()->create([
            'user_id' => Auth::id(),
            'status' => $record->status,
            'phase' => $record->phase,
            'note' => $record->note ?: 'BL record created.',
        ]);
    }
}
