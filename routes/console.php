<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    $notificationCutoff = now()->subDays(config('mobile.notifications.inbox_retention_days'));
    $deliveryCutoff = now()->subDays(config('mobile.notifications.delivery_retention_days'));
    $deviceCutoff = now()->subDays(config('mobile.notifications.device_stale_days'));

    DB::table('notifications')->where('created_at', '<', $notificationCutoff)->delete();
    DB::table('push_delivery_logs')->where('attempted_at', '<', $deliveryCutoff)->delete();
    DB::table('user_devices')
        ->whereNull('disabled_at')
        ->where('last_seen_at', '<', $deviceCutoff)
        ->update(['disabled_at' => now(), 'updated_at' => now()]);
})->dailyAt('02:30')->name('mobile-notification-maintenance')->withoutOverlapping();
