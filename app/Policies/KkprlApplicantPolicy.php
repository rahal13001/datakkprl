<?php

namespace App\Policies;

use App\Models\KkprlApplicant;
use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;

class KkprlApplicantPolicy
{
    public function viewAny(AuthUser $actor): bool
    {
        return $actor instanceof User && $actor->can('ViewAny:KkprlApplicant');
    }

    public function view(AuthUser $actor, KkprlApplicant $account): bool
    {
        return $actor instanceof User && $actor->can('View:KkprlApplicant');
    }

    public function create(AuthUser $actor): bool
    {
        return false;
    }

    public function update(AuthUser $actor, KkprlApplicant $account): bool
    {
        return false;
    }

    public function delete(AuthUser $actor, KkprlApplicant $account): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $actor): bool
    {
        return false;
    }
}
