<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BillOfLading;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillOfLadingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:BillOfLading');
    }

    public function view(User $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('View:BillOfLading');
    }

    public function create(User $authUser): bool
    {
        return $authUser->isAdmin() && $authUser->can('Create:BillOfLading');
    }

    public function update(User $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->isAdmin() && $authUser->can('Update:BillOfLading');
    }

    public function delete(User $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('Delete:BillOfLading')
            && $billOfLading->canBeDeletedAfterRetention();
    }

    public function deleteAny(User $authUser): bool
    {
        return false;
    }

    public function restore(User $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('Restore:BillOfLading');
    }

    public function forceDelete(User $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('ForceDelete:BillOfLading')
            && $billOfLading->canBeDeletedAfterRetention();
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return false;
    }

    public function restoreAny(User $authUser): bool
    {
        return $authUser->can('RestoreAny:BillOfLading');
    }

    public function replicate(User $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('Replicate:BillOfLading');
    }

    public function reorder(User $authUser): bool
    {
        return $authUser->can('Reorder:BillOfLading');
    }
}
