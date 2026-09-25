<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sanitizer = new \App\Domain\Kkprl\ProposalPayloadSanitizer();
$payload = [
    'bag-1' => [
        'applicant_name' => 'John',
        'coordinates' => 'Titik 1: ...',
        'coordinates_raw' => [['lat_dd' => '-0.8']],
    ]
];
file_put_contents('test-sanitizer.out', print_r($sanitizer->sanitize($payload), true));
