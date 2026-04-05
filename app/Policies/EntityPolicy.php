<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Entity;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Entity');
    }

    public function view(AuthUser $authUser, Entity $entity): bool
    {
        return $authUser->can('View:Entity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Entity');
    }

    public function update(AuthUser $authUser, Entity $entity): bool
    {
        return $authUser->can('Update:Entity');
    }

    public function delete(AuthUser $authUser, Entity $entity): bool
    {
        return $authUser->can('Delete:Entity');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Entity');
    }

    public function restore(AuthUser $authUser, Entity $entity): bool
    {
        return $authUser->can('Restore:Entity');
    }

    public function forceDelete(AuthUser $authUser, Entity $entity): bool
    {
        return $authUser->can('ForceDelete:Entity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Entity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Entity');
    }

    public function replicate(AuthUser $authUser, Entity $entity): bool
    {
        return $authUser->can('Replicate:Entity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Entity');
    }

}