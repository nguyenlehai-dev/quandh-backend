<?php
namespace App\Modules\Core\Models;
use Illuminate\Support\Facades\DB;

echo "--- Admin Role State ---\n";
$role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
echo "Role ID: " . $role->id . "\n";

echo "--- System Permissions ---\n";
$perms = Permission::all();
echo "Total permissions available: " . $perms->count() . "\n";

echo "--- Assigning Permissions to Role ---\n";
$role->syncPermissions($perms);
echo "Role Permissions: " . json_encode($role->permissions->pluck('name')) . "\n";

echo "--- Admin User State ---\n";
$user = User::where('email', 'admin@example.com')->first();
if (!$user) {
    echo "Admin user not found!\n";
    exit;
}

$user->assignRole($role);
echo "Direct Permissions: " . json_encode($user->permissions->pluck('name')) . "\n";
echo "All Permissions: " . json_encode($user->getAllPermissions()->pluck('name')) . "\n";
