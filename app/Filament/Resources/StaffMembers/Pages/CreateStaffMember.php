<?php

namespace App\Filament\Resources\StaffMembers\Pages;

use App\Filament\Resources\StaffMembers\StaffMemberResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateStaffMember extends CreateRecord
{
    protected static string $resource = StaffMemberResource::class;

    /** @var list<string> */
    private array $workflowRoles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->workflowRoles = array_values($data['workflow_roles'] ?? []);
        unset($data['workflow_roles']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncAccessRoles($this->record, $this->workflowRoles);
    }

    /**
     * @param  list<string>  $workflowRoles
     */
    public static function syncAccessRoles(User $user, array $workflowRoles): void
    {
        $roleNames = [User::ROLE_PANEL_USER, ...array_intersect($workflowRoles, User::WORKFLOW_ROLES)];

        foreach ($roleNames as $roleName) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if (in_array($roleName, User::WORKFLOW_ROLES, true)) {
                $role->givePermissionTo(Permission::query()
                    ->whereIn('name', ['ViewAny:BillOfLading', 'View:BillOfLading'])
                    ->get());
            }
        }

        $user->syncRoles($roleNames);
    }
}
