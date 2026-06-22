<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LearningMaterial;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningMaterialPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LearningMaterial');
    }

    public function view(AuthUser $authUser, LearningMaterial $learningMaterial): bool
    {
        return $authUser->can('View:LearningMaterial');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LearningMaterial');
    }

    public function update(AuthUser $authUser, LearningMaterial $learningMaterial): bool
    {
        return $authUser->can('Update:LearningMaterial');
    }

    public function delete(AuthUser $authUser, LearningMaterial $learningMaterial): bool
    {
        return $authUser->can('Delete:LearningMaterial');
    }

    public function restore(AuthUser $authUser, LearningMaterial $learningMaterial): bool
    {
        return $authUser->can('Restore:LearningMaterial');
    }

    public function forceDelete(AuthUser $authUser, LearningMaterial $learningMaterial): bool
    {
        return $authUser->can('ForceDelete:LearningMaterial');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LearningMaterial');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LearningMaterial');
    }

    public function replicate(AuthUser $authUser, LearningMaterial $learningMaterial): bool
    {
        return $authUser->can('Replicate:LearningMaterial');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LearningMaterial');
    }

}