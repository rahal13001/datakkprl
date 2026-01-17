<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Get the super_admin role
$role = Role::where('name', 'super_admin')->first();

if (!$role) {
    echo "super_admin role not found!\n";
    exit(1);
}

echo "Role: " . $role->name . "\n";
echo "Guard: " . $role->guard_name . "\n\n";

// Check current permissions
$currentPerms = $role->permissions->pluck('name')->toArray();
echo "Current permissions count: " . count($currentPerms) . "\n";

// Check if assignment permissions exist
$assignmentPerms = Permission::where('name', 'like', '%assignment%')->get();
echo "\nAssignment permissions in DB:\n";
foreach ($assignmentPerms as $p) {
    $hasIt = in_array($p->name, $currentPerms) ? '[ASSIGNED]' : '[NOT ASSIGNED]';
    echo "  - {$p->name} {$hasIt}\n";
}

// Now let's assign them
echo "\nAssigning all assignment permissions to super_admin...\n";
$role->givePermissionTo($assignmentPerms);
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

echo "Done!\n";
