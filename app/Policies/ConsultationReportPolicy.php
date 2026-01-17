<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ConsultationReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConsultationReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ConsultationReport');
    }

    public function view(AuthUser $authUser, ConsultationReport $consultationReport): bool
    {
        return $authUser->can('View:ConsultationReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ConsultationReport');
    }

    public function update(AuthUser $authUser, ConsultationReport $consultationReport): bool
    {
        return $authUser->can('Update:ConsultationReport');
    }

    public function delete(AuthUser $authUser, ConsultationReport $consultationReport): bool
    {
        return $authUser->can('Delete:ConsultationReport');
    }

    public function restore(AuthUser $authUser, ConsultationReport $consultationReport): bool
    {
        return $authUser->can('Restore:ConsultationReport');
    }

    public function forceDelete(AuthUser $authUser, ConsultationReport $consultationReport): bool
    {
        return $authUser->can('ForceDelete:ConsultationReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ConsultationReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ConsultationReport');
    }

    public function replicate(AuthUser $authUser, ConsultationReport $consultationReport): bool
    {
        return $authUser->can('Replicate:ConsultationReport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ConsultationReport');
    }

}