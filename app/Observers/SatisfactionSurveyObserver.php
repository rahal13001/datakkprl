<?php

namespace App\Observers;

use App\Models\SatisfactionSurvey;
use App\Services\MobileNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class SatisfactionSurveyObserver implements ShouldHandleEventsAfterCommit
{
    public function created(SatisfactionSurvey $survey): void
    {
        app(MobileNotificationService::class)->satisfactionSubmitted($survey);
    }
}
