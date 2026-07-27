<?php

namespace App\Services;

use App\Jobs\SendMobilePush;
use App\Models\Assignment;
use App\Models\Client;
use App\Models\PublicFeedback;
use App\Models\SatisfactionSurvey;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\MobileOperationalNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class MobileNotificationService
{
    public function newRequest(Client $client): void
    {
        $recipients = $this->activeUsers()
            ->filter(fn (User $user): bool => $user->can('ViewAny:Client'));

        $this->dispatch($recipients, "client.created:{$client->id}", [
            'event_type' => 'request.created',
            'title' => 'New service request',
            'body' => "Ticket {$client->ticket_number}. Open ServiceKKPRL for details.",
            'ticket_number' => $client->ticket_number,
            'route' => "/requests/{$client->ticket_number}",
            'subject_type' => 'client',
            'subject_id' => (string) $client->id,
            'schema_version' => 1,
        ]);
    }

    public function assignmentCreated(Assignment $assignment): void
    {
        $assignment->loadMissing(['user', 'schedule.client']);
        $client = $assignment->schedule?->client;
        if (! $client) {
            return;
        }

        $direct = collect([$assignment->user])
            ->filter(fn (?User $user): bool => $user?->status && $user->can('View:Client'));
        $oversight = $this->activeUsers()
            ->filter(fn (User $user): bool => $user->can('CreateAssignment'));

        $this->dispatch($direct->merge($oversight), "assignment.created:{$assignment->id}", [
            'event_type' => 'assignment.created',
            'title' => 'New service assignment',
            'body' => "Ticket {$client->ticket_number}. Open ServiceKKPRL for details.",
            'ticket_number' => $client->ticket_number,
            'route' => "/requests/{$client->ticket_number}",
            'subject_type' => 'assignment',
            'subject_id' => (string) $assignment->id,
            'schema_version' => 1,
        ]);
    }

    public function assignmentChanged(Assignment $assignment, ?int $previousUserId = null): void
    {
        $assignment->loadMissing(['user', 'schedule.client']);
        $client = $assignment->schedule?->client;
        if (! $client) {
            return;
        }

        $previousUser = $previousUserId ? User::find($previousUserId) : null;
        $direct = collect([$assignment->user, $previousUser])
            ->filter(fn (?User $user): bool => $user?->status && $user->can('View:Client'));
        $oversight = $this->activeUsers()
            ->filter(fn (User $user): bool => $user->can('UpdateAssignment'));

        $this->dispatch(
            $direct->merge($oversight),
            "assignment.updated:{$assignment->id}:{$assignment->updated_at?->timestamp}",
            [
                'event_type' => 'assignment.updated',
                'title' => 'Service assignment changed',
                'body' => "Ticket {$client->ticket_number}. Open ServiceKKPRL for details.",
                'ticket_number' => $client->ticket_number,
                'route' => "/requests/{$client->ticket_number}",
                'subject_type' => 'assignment',
                'subject_id' => (string) $assignment->id,
                'schema_version' => 1,
            ],
        );
    }

    public function scheduleChanged(Schedule $schedule): void
    {
        $schedule->loadMissing('client.assignments.user');
        $client = $schedule->client;
        if (! $client) {
            return;
        }

        $direct = $client->assignments->pluck('user')
            ->filter(fn (?User $user): bool => $user?->status && $user->can('View:Client'));
        $oversight = $this->activeUsers()
            ->filter(fn (User $user): bool => $user->can('Update:Client'));

        $this->dispatch(
            $direct->merge($oversight),
            "schedule.updated:{$schedule->id}:{$schedule->updated_at?->timestamp}",
            [
                'event_type' => 'schedule.updated',
                'title' => 'Schedule changed',
                'body' => "Ticket {$client->ticket_number}. Open ServiceKKPRL for details.",
                'ticket_number' => $client->ticket_number,
                'route' => "/requests/{$client->ticket_number}",
                'subject_type' => 'schedule',
                'subject_id' => (string) $schedule->id,
                'schema_version' => 1,
            ],
        );
    }

    public function satisfactionSubmitted(SatisfactionSurvey $survey): void
    {
        $survey->loadMissing('client.assignments.user');
        $client = $survey->client;
        if (! $client) {
            return;
        }

        $direct = $client->assignments->pluck('user')
            ->filter(fn (?User $user): bool => $user?->status && $user->can('View:Client'));
        $oversight = $this->activeUsers()
            ->filter(fn (User $user): bool => $user->can('ViewAny:SatisfactionSurvey'));

        $this->dispatch($direct->merge($oversight), "satisfaction.created:{$survey->id}", [
            'event_type' => 'satisfaction.created',
            'title' => 'New service assessment',
            'body' => "Ticket {$client->ticket_number}. Open ServiceKKPRL for details.",
            'ticket_number' => $client->ticket_number,
            'route' => "/feedback/satisfaction/{$survey->id}",
            'subject_type' => 'satisfaction_survey',
            'subject_id' => (string) $survey->id,
            'schema_version' => 1,
        ]);
    }

    public function publicFeedbackSubmitted(PublicFeedback $feedback): void
    {
        $feedback->loadMissing('users');
        $direct = $feedback->users
            ->filter(fn (User $user): bool => $user->status && $user->can('View:PublicFeedback'));
        $oversight = $this->activeUsers()
            ->filter(fn (User $user): bool => $user->can('ViewAny:PublicFeedback'));

        $this->dispatch($direct->merge($oversight), "public-feedback.created:{$feedback->id}", [
            'event_type' => 'public_feedback.created',
            'title' => 'New public feedback',
            'body' => 'Open ServiceKKPRL to review the feedback.',
            'ticket_number' => null,
            'route' => "/feedback/public/{$feedback->id}",
            'subject_type' => 'public_feedback',
            'subject_id' => (string) $feedback->id,
            'schema_version' => 1,
        ]);
    }

    private function dispatch(Collection $recipients, string $eventKey, array $payload): void
    {
        $recipients
            ->filter()
            ->unique('id')
            ->each(function (User $user) use ($eventKey, $payload): void {
                $notificationId = Uuid::uuid5(
                    Uuid::NAMESPACE_URL,
                    "servicekkprl:{$eventKey}:{$user->id}",
                )->toString();

                if ($user->notifications()->whereKey($notificationId)->exists()) {
                    return;
                }

                $user->notify(new MobileOperationalNotification($payload, $notificationId));

                DB::afterCommit(fn () => SendMobilePush::dispatch($user->id, $notificationId)
                    ->onQueue('push'));
            });
    }

    private function activeUsers(): Collection
    {
        return User::query()
            ->where('status', true)
            ->with('roles.permissions')
            ->get();
    }
}
