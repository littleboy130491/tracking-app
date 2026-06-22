<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BillOfLading;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillOfLadingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BillOfLading');
    }

    public function view(AuthUser $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('View:BillOfLading');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BillOfLading');
    }

    public function update(AuthUser $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('Update:BillOfLading');
    }

    public function delete(AuthUser $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('Delete:BillOfLading');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BillOfLading');
    }

    public function restore(AuthUser $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('Restore:BillOfLading');
    }

    public function forceDelete(AuthUser $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('ForceDelete:BillOfLading');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BillOfLading');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BillOfLading');
    }

    public function replicate(AuthUser $authUser, BillOfLading $billOfLading): bool
    {
        return $authUser->can('Replicate:BillOfLading');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BillOfLading');
    }

}