<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$proposals = \App\Models\KkprlProposal::all();
foreach ($proposals as $p) {
    echo "ID {$p->id}:\n";
    print_r($p->payload['bag-1'] ?? 'No bag-1');
    echo "\n";
}
