<?php

namespace App\Observers;

use App\Models\Regulation;
use Illuminate\Support\Facades\Cache;

class RegulationObserver
{
    public function saved(Regulation $regulation): void
    {
        Cache::forget('regulations_published');
    }

    public function deleted(Regulation $regulation): void
    {
        Cache::forget('regulations_published');
    }

    public function restored(Regulation $regulation): void
    {
        Cache::forget('regulations_published');
    }
}
