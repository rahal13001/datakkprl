<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RunningText;
use Illuminate\Auth\Access\HandlesAuthorization;

class RunningTextPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RunningText');
    }

    public function view(AuthUser $authUser, RunningText $runningText): bool
    {
        return $authUser->can('View:RunningText');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RunningText');
    }

    public function update(AuthUser $authUser, RunningText $runningText): bool
    {
        return $authUser->can('Update:RunningText');
    }

    public function delete(AuthUser $authUser, RunningText $runningText): bool
    {
        return $authUser->can('Delete:RunningText');
    }

    public function restore(AuthUser $authUser, RunningText $runningText): bool
    {
        return $authUser->can('Restore:RunningText');
    }

    public function forceDelete(AuthUser $authUser, RunningText $runningText): bool
    {
        return $authUser->can('ForceDelete:RunningText');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RunningText');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RunningText');
    }

    public function replicate(AuthUser $authUser, RunningText $runningText): bool
    {
        return $authUser->can('Replicate:RunningText');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RunningText');
    }

}