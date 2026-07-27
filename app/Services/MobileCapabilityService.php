<?php

namespace App\Services;

use App\Models\User;

class MobileCapabilityService
{
    public function for(User $user): array
    {
        return [
            'dashboard' => [
                'view' => $this->can($user, 'View:Dashboard') || $this->can($user, 'ViewAny:Client'),
            ],
            'clients' => [
                'list' => $this->can($user, 'ViewAny:Client'),
                'view' => $this->can($user, 'View:Client'),
                'create' => false,
                'update' => $this->can($user, 'Update:Client'),
                'delete' => false,
            ],
            'schedules' => [
                'create' => $this->can($user, 'Update:Client'),
                'update' => $this->can($user, 'Update:Client'),
                'delete' => false,
            ],
            'assignments' => [
                'list' => $this->canAny($user, ['ViewAnyAssignment', 'ViewAssignment', 'View:Client']),
                'view' => $this->canAny($user, ['ViewAssignment', 'View:Client']),
                'create' => $this->can($user, 'CreateAssignment'),
                'update' => $this->can($user, 'UpdateAssignment'),
                'delete' => false,
            ],
            'consultation_reports' => [
                'list' => $this->can($user, 'ViewAny:ConsultationReport'),
                'view' => $this->can($user, 'View:ConsultationReport'),
                'create' => $this->can($user, 'Create:ConsultationReport'),
                'update' => $this->can($user, 'Update:ConsultationReport'),
                'delete' => false,
            ],
            'berita_acara' => [
                'view' => $this->can($user, 'View:Client'),
                'create' => $this->can($user, 'Update:Client'),
                'update' => $this->can($user, 'Update:Client'),
                'delete' => false,
            ],
            'satisfaction_surveys' => [
                'list' => $this->can($user, 'ViewAny:SatisfactionSurvey'),
                'view' => $this->can($user, 'View:SatisfactionSurvey'),
            ],
            'public_feedback' => [
                'list' => $this->can($user, 'ViewAny:PublicFeedback'),
                'view' => $this->can($user, 'View:PublicFeedback'),
            ],
            'notifications' => [
                'list' => true,
                'mark_read' => true,
            ],
        ];
    }

    public function hasMobileAccess(User $user): bool
    {
        $capabilities = collect($this->for($user))
            ->except('notifications');

        return $capabilities
            ->flatten()
            ->contains(true);
    }

    private function can(User $user, string $permission): bool
    {
        return $user->can($permission);
    }

    private function canAny(User $user, array $permissions): bool
    {
        return collect($permissions)->contains(fn (string $permission): bool => $user->can($permission));
    }
}
