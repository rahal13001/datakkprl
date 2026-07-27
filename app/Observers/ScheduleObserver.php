<?php

namespace App\Observers;

use App\Models\Schedule;
use App\Services\MobileNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ScheduleObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Schedule $schedule): void
    {
        if ($schedule->client?->created_at?->lt(now()->subSeconds(5))) {
            app(MobileNotificationService::class)->scheduleChanged($schedule);
        }
    }

    public function updated(Schedule $schedule): void
    {
        if ($schedule->wasChanged(['date', 'start_time', 'end_time', 'is_online', 'meeting_link_encrypted'])) {
            app(MobileNotificationService::class)->scheduleChanged($schedule);
        }
    }
}
