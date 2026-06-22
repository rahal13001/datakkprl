<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LearningCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LearningCategory');
    }

    public function view(AuthUser $authUser, LearningCategory $learningCategory): bool
    {
        return $authUser->can('View:LearningCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LearningCategory');
    }

    public function update(AuthUser $authUser, LearningCategory $learningCategory): bool
    {
        return $authUser->can('Update:LearningCategory');
    }

    public function delete(AuthUser $authUser, LearningCategory $learningCategory): bool
    {
        return $authUser->can('Delete:LearningCategory');
    }

    public function restore(AuthUser $authUser, LearningCategory $learningCategory): bool
    {
        return $authUser->can('Restore:LearningCategory');
    }

    public function forceDelete(AuthUser $authUser, LearningCategory $learningCategory): bool
    {
        return $authUser->can('ForceDelete:LearningCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LearningCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LearningCategory');
    }

    public function replicate(AuthUser $authUser, LearningCategory $learningCategory): bool
    {
        return $authUser->can('Replicate:LearningCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LearningCategory');
    }

}