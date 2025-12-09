<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Activity::observe(\App\Observers\ActivityObserver::class);
        \App\Models\Faq::observe(\App\Observers\FaqObserver::class);
        \App\Models\Regulation::observe(\App\Observers\RegulationObserver::class);
        \App\Models\Client::observe(\App\Observers\ClientObserver::class);
        \App\Models\Assignment::observe(\App\Observers\AssignmentObserver::class);
    }
}
