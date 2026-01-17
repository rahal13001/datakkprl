<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Regulation;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegulationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Regulation');
    }

    public function view(AuthUser $authUser, Regulation $regulation): bool
    {
        return $authUser->can('View:Regulation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Regulation');
    }

    public function update(AuthUser $authUser, Regulation $regulation): bool
    {
        return $authUser->can('Update:Regulation');
    }

    public function delete(AuthUser $authUser, Regulation $regulation): bool
    {
        return $authUser->can('Delete:Regulation');
    }

    public function restore(AuthUser $authUser, Regulation $regulation): bool
    {
        return $authUser->can('Restore:Regulation');
    }

    public function forceDelete(AuthUser $authUser, Regulation $regulation): bool
    {
        return $authUser->can('ForceDelete:Regulation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Regulation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Regulation');
    }

    public function replicate(AuthUser $authUser, Regulation $regulation): bool
    {
        return $authUser->can('Replicate:Regulation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Regulation');
    }

}