<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class KkprlApplicantPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = ['ViewAny:KkprlApplicant', 'View:KkprlApplicant', 'Approve:KkprlApplicant', 'ResetPassword:KkprlApplicant', 'AssignOwner:KkprlApplicant'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        foreach (['admin', 'pimpinan', 'super_admin'] as $role) {
            Role::findOrCreate($role, 'web')->givePermissionTo($permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
