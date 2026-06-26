<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PublicFeedback;
use Illuminate\Auth\Access\HandlesAuthorization;

class PublicFeedbackPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PublicFeedback');
    }

    public function view(AuthUser $authUser, PublicFeedback $publicFeedback): bool
    {
        return $authUser->can('View:PublicFeedback');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PublicFeedback');
    }

    public function update(AuthUser $authUser, PublicFeedback $publicFeedback): bool
    {
        return $authUser->can('Update:PublicFeedback');
    }

    public function delete(AuthUser $authUser, PublicFeedback $publicFeedback): bool
    {
        return $authUser->can('Delete:PublicFeedback');
    }

    public function restore(AuthUser $authUser, PublicFeedback $publicFeedback): bool
    {
        return $authUser->can('Restore:PublicFeedback');
    }

    public function forceDelete(AuthUser $authUser, PublicFeedback $publicFeedback): bool
    {
        return $authUser->can('ForceDelete:PublicFeedback');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PublicFeedback');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PublicFeedback');
    }

    public function replicate(AuthUser $authUser, PublicFeedback $publicFeedback): bool
    {
        return $authUser->can('Replicate:PublicFeedback');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PublicFeedback');
    }

}