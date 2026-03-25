<?php

$role = \App\Modules\Core\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
$role->syncPermissions(\App\Modules\Core\Models\Permission::all());
$user = \App\Modules\Core\Models\User::where('email', 'admin@example.com')->first();
$user->assignRole($role);
echo "Permissions granted to admin@example.com!\n";
