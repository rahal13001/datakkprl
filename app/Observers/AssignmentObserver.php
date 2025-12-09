<?php

namespace App\Observers;

use App\Models\Assignment;
use App\Mail\StaffAssigned;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AssignmentObserver
{
    /**
     * Handle the Assignment "created" event.
     */
    public function created(Assignment $assignment): void
    {
        try {
            // Load relationships needed for email
            $assignment->load(['user', 'schedule.client.service']);

            if ($assignment->user && $assignment->user->email) {
                Mail::to($assignment->user->email)->send(new StaffAssigned($assignment));
                Log::info("StaffAssigned email sent to {$assignment->user->email} for Assignment ID {$assignment->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send StaffAssigned email: " . $e->getMessage());
        }
    }

    /**
     * Handle the Assignment "updated" event.
     */
    public function updated(Assignment $assignment): void
    {
        // Optional: Send update email if schedule changes?
        // For now, user only asked for "assigned" confirmation.
    }

    /**
     * Handle the Assignment "deleted" event.
     */
    public function deleted(Assignment $assignment): void
    {
        //
    }

    /**
     * Handle the Assignment "restored" event.
     */
    public function restored(Assignment $assignment): void
    {
        //
    }

    /**
     * Handle the Assignment "force deleted" event.
     */
    public function forceDeleted(Assignment $assignment): void
    {
        //
    }
}
