<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KkprlProposalEditApproval;
use Illuminate\Auth\Access\HandlesAuthorization;

class KkprlProposalEditApprovalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KkprlProposalEditApproval');
    }

    public function view(AuthUser $authUser, KkprlProposalEditApproval $kkprlProposalEditApproval): bool
    {
        return $authUser->can('View:KkprlProposalEditApproval');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KkprlProposalEditApproval');
    }

    public function update(AuthUser $authUser, KkprlProposalEditApproval $kkprlProposalEditApproval): bool
    {
        return $authUser->can('Update:KkprlProposalEditApproval');
    }

    public function delete(AuthUser $authUser, KkprlProposalEditApproval $kkprlProposalEditApproval): bool
    {
        return $authUser->can('Delete:KkprlProposalEditApproval');
    }

    public function restore(AuthUser $authUser, KkprlProposalEditApproval $kkprlProposalEditApproval): bool
    {
        return $authUser->can('Restore:KkprlProposalEditApproval');
    }

    public function forceDelete(AuthUser $authUser, KkprlProposalEditApproval $kkprlProposalEditApproval): bool
    {
        return $authUser->can('ForceDelete:KkprlProposalEditApproval');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KkprlProposalEditApproval');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KkprlProposalEditApproval');
    }

    public function replicate(AuthUser $authUser, KkprlProposalEditApproval $kkprlProposalEditApproval): bool
    {
        return $authUser->can('Replicate:KkprlProposalEditApproval');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KkprlProposalEditApproval');
    }

}