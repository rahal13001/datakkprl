<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SatisfactionSurveyResult;
use Illuminate\Auth\Access\HandlesAuthorization;

class SatisfactionSurveyResultPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SatisfactionSurveyResult');
    }

    public function view(AuthUser $authUser, SatisfactionSurveyResult $satisfactionSurveyResult): bool
    {
        return $authUser->can('View:SatisfactionSurveyResult');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SatisfactionSurveyResult');
    }

    public function update(AuthUser $authUser, SatisfactionSurveyResult $satisfactionSurveyResult): bool
    {
        return $authUser->can('Update:SatisfactionSurveyResult');
    }

    public function delete(AuthUser $authUser, SatisfactionSurveyResult $satisfactionSurveyResult): bool
    {
        return $authUser->can('Delete:SatisfactionSurveyResult');
    }

    public function restore(AuthUser $authUser, SatisfactionSurveyResult $satisfactionSurveyResult): bool
    {
        return $authUser->can('Restore:SatisfactionSurveyResult');
    }

    public function forceDelete(AuthUser $authUser, SatisfactionSurveyResult $satisfactionSurveyResult): bool
    {
        return $authUser->can('ForceDelete:SatisfactionSurveyResult');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SatisfactionSurveyResult');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SatisfactionSurveyResult');
    }

    public function replicate(AuthUser $authUser, SatisfactionSurveyResult $satisfactionSurveyResult): bool
    {
        return $authUser->can('Replicate:SatisfactionSurveyResult');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SatisfactionSurveyResult');
    }

}