<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$indexes = Illuminate\Support\Facades\Schema::getIndexes('kkprl_proposal_documents');
print_r($indexes);
