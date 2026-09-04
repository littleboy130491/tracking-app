<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:Company');
    }

    public function view(User $authUser, Company $company): bool
    {
        return $authUser->can('View:Company');
    }

    public function create(User $authUser): bool
    {
        return $authUser->isAdmin() && $authUser->can('Create:Company');
    }

    public function update(User $authUser, Company $company): bool
    {
        return $authUser->isAdmin() && $authUser->can('Update:Company');
    }

    public function delete(User $authUser, Company $company): bool
    {
        return $authUser->isAdmin()
            && $authUser->can('Delete:Company')
            && $company->billOfLadings()->withTrashed()->doesntExist();
    }

    public function deleteAny(User $authUser): bool
    {
        return false;
    }

    public function restore(User $authUser, Company $company): bool
    {
        return $authUser->can('Restore:Company');
    }

    public function forceDelete(User $authUser, Company $company): bool
    {
        return false;
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return false;
    }
}
