<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(1);
echo "User: " . $user->name . "\n";
echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n\n";

$permissions = [
    'view_any_assignment',
    'view_assignment',
    'create_assignment',
    'update_assignment',
    'delete_assignment',
];

foreach ($permissions as $p) {
    $result = $user->can($p) ? 'YES' : 'NO';
    echo "$p: $result\n";
}
