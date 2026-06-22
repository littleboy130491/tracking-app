<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Concerns\SyncsCustomerDisplayName;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    use SyncsCustomerDisplayName;

    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->prepareCustomerData($data);
        $data['password'] = Hash::make(Str::random(32));

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User $record */
        $record = $this->record;

        Role::query()->firstOrCreate([
            'name' => User::ROLE_CUSTOMER,
            'guard_name' => 'web',
        ]);

        $record->assignRole(User::ROLE_CUSTOMER);
    }
}
