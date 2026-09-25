<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KkprlProposal;
use Illuminate\Auth\Access\HandlesAuthorization;

class KkprlProposalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KkprlProposal');
    }

    public function view(AuthUser $authUser, KkprlProposal $kkprlProposal): bool
    {
        return $authUser->can('View:KkprlProposal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KkprlProposal');
    }

    public function update(AuthUser $authUser, KkprlProposal $kkprlProposal): bool
    {
        return $authUser->can('Update:KkprlProposal');
    }

    public function delete(AuthUser $authUser, KkprlProposal $kkprlProposal): bool
    {
        return $authUser->can('Delete:KkprlProposal');
    }

    public function restore(AuthUser $authUser, KkprlProposal $kkprlProposal): bool
    {
        return $authUser->can('Restore:KkprlProposal');
    }

    public function forceDelete(AuthUser $authUser, KkprlProposal $kkprlProposal): bool
    {
        return $authUser->can('ForceDelete:KkprlProposal');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KkprlProposal');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KkprlProposal');
    }

    public function replicate(AuthUser $authUser, KkprlProposal $kkprlProposal): bool
    {
        return $authUser->can('Replicate:KkprlProposal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KkprlProposal');
    }

}