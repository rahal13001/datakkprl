<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LearningActivityLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningActivityLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LearningActivityLog');
    }

    public function view(AuthUser $authUser, LearningActivityLog $learningActivityLog): bool
    {
        return $authUser->can('View:LearningActivityLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LearningActivityLog');
    }

    public function update(AuthUser $authUser, LearningActivityLog $learningActivityLog): bool
    {
        return $authUser->can('Update:LearningActivityLog');
    }

    public function delete(AuthUser $authUser, LearningActivityLog $learningActivityLog): bool
    {
        return $authUser->can('Delete:LearningActivityLog');
    }

    public function restore(AuthUser $authUser, LearningActivityLog $learningActivityLog): bool
    {
        return $authUser->can('Restore:LearningActivityLog');
    }

    public function forceDelete(AuthUser $authUser, LearningActivityLog $learningActivityLog): bool
    {
        return $authUser->can('ForceDelete:LearningActivityLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LearningActivityLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LearningActivityLog');
    }

    public function replicate(AuthUser $authUser, LearningActivityLog $learningActivityLog): bool
    {
        return $authUser->can('Replicate:LearningActivityLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LearningActivityLog');
    }

}