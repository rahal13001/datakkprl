<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\KkprlAiService();
$res = $service->chat([['role' => 'user', 'content' => 'Hello']], 'You are helpful');
echo "Result: " . ($res ? "SUCCESS: $res" : "FAIL") . "\n";
