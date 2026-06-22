<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ServicePerformanceResult;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePerformanceResultPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ServicePerformanceResult');
    }

    public function view(AuthUser $authUser, ServicePerformanceResult $servicePerformanceResult): bool
    {
        return $authUser->can('View:ServicePerformanceResult');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ServicePerformanceResult');
    }

    public function update(AuthUser $authUser, ServicePerformanceResult $servicePerformanceResult): bool
    {
        return $authUser->can('Update:ServicePerformanceResult');
    }

    public function delete(AuthUser $authUser, ServicePerformanceResult $servicePerformanceResult): bool
    {
        return $authUser->can('Delete:ServicePerformanceResult');
    }

    public function restore(AuthUser $authUser, ServicePerformanceResult $servicePerformanceResult): bool
    {
        return $authUser->can('Restore:ServicePerformanceResult');
    }

    public function forceDelete(AuthUser $authUser, ServicePerformanceResult $servicePerformanceResult): bool
    {
        return $authUser->can('ForceDelete:ServicePerformanceResult');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ServicePerformanceResult');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ServicePerformanceResult');
    }

    public function replicate(AuthUser $authUser, ServicePerformanceResult $servicePerformanceResult): bool
    {
        return $authUser->can('Replicate:ServicePerformanceResult');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ServicePerformanceResult');
    }

}