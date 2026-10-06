<?php

namespace App\Policies;

use App\Models\ProposalGuide;
use App\Models\User;

class ProposalGuidePolicy
{
    // The same content administrators retain access after the guide is separated.
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:LearningMaterial');
    }

    public function view(User $user, ProposalGuide $guide): bool
    {
        return $user->can('View:LearningMaterial');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:LearningMaterial');
    }

    public function update(User $user, ProposalGuide $guide): bool
    {
        return $user->can('Update:LearningMaterial');
    }

    public function delete(User $user, ProposalGuide $guide): bool
    {
        return false;
    }
}
