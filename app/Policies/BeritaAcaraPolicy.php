<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BeritaAcara;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * BeritaAcara is managed via a RelationManager on ClientResource,
 * so permissions are inherited from the parent Client resource.
 * All actions are allowed if the user can access the Client.
 */
class BeritaAcaraPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('View:Client');
    }

    public function view(AuthUser $authUser, BeritaAcara $beritaAcara): bool
    {
        return $authUser->can('View:Client');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Client');
    }

    public function update(AuthUser $authUser, BeritaAcara $beritaAcara): bool
    {
        return $authUser->can('Update:Client');
    }

    public function delete(AuthUser $authUser, BeritaAcara $beritaAcara): bool
    {
        return $authUser->can('Update:Client');
    }

    public function restore(AuthUser $authUser, BeritaAcara $beritaAcara): bool
    {
        return $authUser->can('Update:Client');
    }

    public function forceDelete(AuthUser $authUser, BeritaAcara $beritaAcara): bool
    {
        return $authUser->can('Delete:Client');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Client');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Client');
    }

    public function replicate(AuthUser $authUser, BeritaAcara $beritaAcara): bool
    {
        return $authUser->can('Update:Client');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Client');
    }
}