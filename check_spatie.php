<?php
$user = \App\Modules\Core\Models\User::where('email', 'admin@example.com')->first();
echo "User guard name property: " . $user->guard_name . "\n";
echo "Roles relation: " . json_encode($user->roles()->get()->pluck('name')) . "\n";
echo "Permissions relation: " . json_encode($user->permissions()->get()->pluck('name')) . "\n";
echo "Active Guard: " . config('auth.defaults.guard') . "\n";

// Let's create a role and permission matching the global default guard
$guard = config('auth.defaults.guard') ?? 'web';
echo "Using guard: $guard\n";

$role = \App\Modules\Core\Models\Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => $guard]);
$permission = \App\Modules\Core\Models\Permission::firstOrCreate(['name' => 'system.all', 'guard_name' => $guard]);

$role->givePermissionTo($permission);
$user->assignRole($role);

app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

echo "After superadmin setup:\n";
echo "All Permissions: " . json_encode($user->getAllPermissions()->pluck('name')) . "\n";
