<?php

namespace App\Filament\Resources\BillOfLadings\Pages;

use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use App\Filament\Resources\BillOfLadings\Actions\PostProgressUpdateAction;
use App\Models\BillOfLading;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditBillOfLading extends EditRecord
{
    protected static string $resource = BillOfLadingResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected ?string $originalStatus = null;

    protected ?string $originalPhase = null;

    protected ?string $originalNote = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var BillOfLading $record */
        $record = $this->getRecord();

        $this->originalStatus = $record->status;
        $this->originalPhase = $record->phase;
        $this->originalNote = $record->note;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        /** @var BillOfLading $record */
        if (
            $record->status !== $this->originalStatus
            || $record->phase !== $this->originalPhase
            || $record->note !== $this->originalNote
        ) {
            $record->updates()->create([
                'user_id' => Auth::id(),
                'status' => $record->status,
                'phase' => $record->phase,
                'note' => $record->note,
            ]);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            PostProgressUpdateAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
