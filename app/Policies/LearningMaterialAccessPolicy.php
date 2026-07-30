<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LearningMaterialAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningMaterialAccessPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LearningMaterialAccess');
    }

    public function view(AuthUser $authUser, LearningMaterialAccess $learningMaterialAccess): bool
    {
        return $authUser->can('View:LearningMaterialAccess');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LearningMaterialAccess');
    }

    public function update(AuthUser $authUser, LearningMaterialAccess $learningMaterialAccess): bool
    {
        return $authUser->can('Update:LearningMaterialAccess');
    }

    public function delete(AuthUser $authUser, LearningMaterialAccess $learningMaterialAccess): bool
    {
        return $authUser->can('Delete:LearningMaterialAccess');
    }

    public function restore(AuthUser $authUser, LearningMaterialAccess $learningMaterialAccess): bool
    {
        return $authUser->can('Restore:LearningMaterialAccess');
    }

    public function forceDelete(AuthUser $authUser, LearningMaterialAccess $learningMaterialAccess): bool
    {
        return $authUser->can('ForceDelete:LearningMaterialAccess');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LearningMaterialAccess');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LearningMaterialAccess');
    }

    public function replicate(AuthUser $authUser, LearningMaterialAccess $learningMaterialAccess): bool
    {
        return $authUser->can('Replicate:LearningMaterialAccess');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LearningMaterialAccess');
    }

}