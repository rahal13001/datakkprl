<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ConsultationLocation;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConsultationLocationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ConsultationLocation');
    }

    public function view(AuthUser $authUser, ConsultationLocation $consultationLocation): bool
    {
        return $authUser->can('View:ConsultationLocation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ConsultationLocation');
    }

    public function update(AuthUser $authUser, ConsultationLocation $consultationLocation): bool
    {
        return $authUser->can('Update:ConsultationLocation');
    }

    public function delete(AuthUser $authUser, ConsultationLocation $consultationLocation): bool
    {
        return $authUser->can('Delete:ConsultationLocation');
    }

    public function restore(AuthUser $authUser, ConsultationLocation $consultationLocation): bool
    {
        return $authUser->can('Restore:ConsultationLocation');
    }

    public function forceDelete(AuthUser $authUser, ConsultationLocation $consultationLocation): bool
    {
        return $authUser->can('ForceDelete:ConsultationLocation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ConsultationLocation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ConsultationLocation');
    }

    public function replicate(AuthUser $authUser, ConsultationLocation $consultationLocation): bool
    {
        return $authUser->can('Replicate:ConsultationLocation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ConsultationLocation');
    }

}