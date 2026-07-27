<?php

namespace App\Observers;

use App\Models\PublicFeedback;
use App\Services\MobileNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class PublicFeedbackObserver implements ShouldHandleEventsAfterCommit
{
    public function created(PublicFeedback $feedback): void
    {
        app(MobileNotificationService::class)->publicFeedbackSubmitted($feedback);
    }
}
