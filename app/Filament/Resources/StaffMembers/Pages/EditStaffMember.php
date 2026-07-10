<?php

namespace App\Filament\Resources\StaffMembers\Pages;

use App\Filament\Resources\StaffMembers\Pages\CreateStaffMember as StaffAccess;
use App\Filament\Resources\StaffMembers\StaffMemberResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditStaffMember extends EditRecord
{
    protected static string $resource = StaffMemberResource::class;

    /** @var list<string> */
    private array $workflowRoles = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->record;
        $data['workflow_roles'] = $record->roles()
            ->whereIn('name', User::WORKFLOW_ROLES)
            ->pluck('name')
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->workflowRoles = array_values($data['workflow_roles'] ?? []);
        unset($data['workflow_roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $record */
        $record = $this->record;
        StaffAccess::syncAccessRoles($record, $this->workflowRoles);
    }
}
