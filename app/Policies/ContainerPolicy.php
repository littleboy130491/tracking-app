<?php

namespace App\Policies;

use App\Models\Container;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContainerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:Container');
    }

    public function view(User $authUser, Container $container): bool
    {
        return $authUser->can('View:Container');
    }

    public function create(User $authUser): bool
    {
        return $authUser->isAdmin() && $authUser->can('Create:Container');
    }

    public function update(User $authUser, Container $container): bool
    {
        return $authUser->isAdmin() && $authUser->can('Update:Container');
    }

    public function delete(User $authUser, Container $container): bool
    {
        return $authUser->isAdmin() && $authUser->can('Delete:Container');
    }

    public function deleteAny(User $authUser): bool
    {
        return false;
    }

    public function restore(User $authUser, Container $container): bool
    {
        return $authUser->can('Restore:Container');
    }

    public function forceDelete(User $authUser, Container $container): bool
    {
        return false;
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return false;
    }
}
