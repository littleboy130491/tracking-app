<?php

namespace App\Filament\Resources\BillOfLadings\Pages;

use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use App\Models\BillOfLading;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;

class CreateBillOfLading extends CreateRecord
{
    protected static string $resource = BillOfLadingResource::class;

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
