<?php

namespace App\Observers;

use App\Models\Activity;
use Illuminate\Support\Str;

class ActivityObserver
{
    public function creating(Activity $activity): void
    {
        // Check if an activity exists for the same subject and date
        $existingActivity = Activity::where('subject_id', $activity->subject_id)
            ->where('date', $activity->date)
            ->first();

        if ($existingActivity) {
            // Reuse the existing code
            $activity->activity_code = $existingActivity->activity_code;
        } else {
            // Generate a new unique code
            // Format: ACT-YYYYMMDD-RANDOM
            $dateStr = $activity->date->format('Ymd');
            $activity->activity_code = 'ACT-' . $dateStr . '-' . strtoupper(Str::random(5));
        }
    }
}
