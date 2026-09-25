<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\KkprlAiService();
$messages = [
    ['role' => 'ai', 'content' => 'Halo! Bisa ceritakan kegiatan apa saja yang ada di sekitar perairan?'],
    ['role' => 'user', 'content' => 'disebelah saya ada dermaga dengan jarak 40 Meter'],
    ['role' => 'ai', 'content' => 'Terima kasih, dari mana Anda mengetahuinya?'],
    ['role' => 'user', 'content' => 'saya dari pengamatan langsung di lapangan']
];
$res = $service->chat($messages, 'Anda adalah asisten ahli.');
echo "Result: " . ($res ? "SUCCESS: $res" : "FAIL") . "\n";
