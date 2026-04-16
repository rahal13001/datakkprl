<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$attendees = \App\Models\BeritaAcaraAttendee::whereNull('token')->get();
foreach ($attendees as $attendee) {
    $attendee->token = (string) \Illuminate\Support\Str::uuid();
    $attendee->save();
}

echo "Tokens generated for " . $attendees->count() . " attendees.\n";
