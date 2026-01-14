<?php

namespace App\Observers;

use App\Models\Assignment;
use App\Models\Client;
use App\Mail\StaffAssigned;
use App\Mail\StatusScheduledMail;
use App\Mail\StatusWaitingMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AssignmentObserver
{
    /**
     * Handle the Assignment "created" event.
     */
    public function created(Assignment $assignment): void
    {
        // 1. Send Email to Staff
        try {
            $assignment->load(['user', 'schedule.client.service']);

            if ($assignment->user && $assignment->user->email) {
                Mail::to($assignment->user->email)->send(new StaffAssigned($assignment));
                Log::info("StaffAssigned email sent to {$assignment->user->email} for Assignment ID {$assignment->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send StaffAssigned email: " . $e->getMessage());
        }

        // 2. Update Client Status to Scheduled
        try {
            $client = $assignment->schedule->client;
            if ($client && $client->status === 'waiting') {
                $client->update(['status' => 'scheduled']);
                
                if ($client->email) {
                    Mail::to($client->email)->send(new StatusScheduledMail($client));
                    Log::info("StatusScheduledMail sent to {$client->email}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to update client status/email on assignment creation: " . $e->getMessage());
        }
    }

    /**
     * Handle the Assignment "deleted" event.
     */
    public function deleted(Assignment $assignment): void
    {
        $this->checkIfRevertToWaiting($assignment);
    }

    /**
     * Handle the Assignment "force deleted" event.
     */
    public function forceDeleted(Assignment $assignment): void
    {
        $this->checkIfRevertToWaiting($assignment);
    }

    /**
     * Check if client should revert to "waiting" status.
     */
    protected function checkIfRevertToWaiting(Assignment $assignment): void
    {
        try {
            // Reload schedule->client to ensure we have the latest data
            $assignment->load('schedule.client');
            $client = $assignment->schedule->client;

            if ($client && $client->status === 'scheduled') {
                // Check if there are any ACTIVE assignments left for this client
                // We assume hasManyThrough relationship 'assignments' is defined in Client model
                $activeAssignmentsCount = $client->assignments()->count();

                if ($activeAssignmentsCount === 0) {
                    $client->update(['status' => 'waiting']);

                    if ($client->email) {
                        Mail::to($client->email)->send(new StatusWaitingMail($client));
                        Log::info("StatusWaitingMail sent to {$client->email}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to check revert status on assignment deletion: " . $e->getMessage());
        }
    }
}
