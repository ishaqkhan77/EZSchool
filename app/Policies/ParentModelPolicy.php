<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ParentModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParentModelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ParentModel');
    }

    public function view(AuthUser $authUser, ParentModel $parentModel): bool
    {
        return $authUser->can('View:ParentModel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ParentModel');
    }

    public function update(AuthUser $authUser, ParentModel $parentModel): bool
    {
        return $authUser->can('Update:ParentModel');
    }

    public function delete(AuthUser $authUser, ParentModel $parentModel): bool
    {
        return $authUser->can('Delete:ParentModel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ParentModel');
    }

    public function restore(AuthUser $authUser, ParentModel $parentModel): bool
    {
        return $authUser->can('Restore:ParentModel');
    }

    public function forceDelete(AuthUser $authUser, ParentModel $parentModel): bool
    {
        return $authUser->can('ForceDelete:ParentModel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ParentModel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ParentModel');
    }

    public function replicate(AuthUser $authUser, ParentModel $parentModel): bool
    {
        return $authUser->can('Replicate:ParentModel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ParentModel');
    }

}