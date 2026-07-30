<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LearningSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningSessionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LearningSession');
    }

    public function view(AuthUser $authUser, LearningSession $learningSession): bool
    {
        return $authUser->can('View:LearningSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LearningSession');
    }

    public function update(AuthUser $authUser, LearningSession $learningSession): bool
    {
        return $authUser->can('Update:LearningSession');
    }

    public function delete(AuthUser $authUser, LearningSession $learningSession): bool
    {
        return $authUser->can('Delete:LearningSession');
    }

    public function restore(AuthUser $authUser, LearningSession $learningSession): bool
    {
        return $authUser->can('Restore:LearningSession');
    }

    public function forceDelete(AuthUser $authUser, LearningSession $learningSession): bool
    {
        return $authUser->can('ForceDelete:LearningSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LearningSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LearningSession');
    }

    public function replicate(AuthUser $authUser, LearningSession $learningSession): bool
    {
        return $authUser->can('Replicate:LearningSession');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LearningSession');
    }

}