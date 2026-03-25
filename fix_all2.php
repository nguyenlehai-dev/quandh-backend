<?php
use Illuminate\Support\Facades\DB;
use App\Modules\Core\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\Permission;

echo "--- Fixing Guards ---\n";
DB::table('permissions')->update(['guard_name' => 'api']);
DB::table('roles')->update(['guard_name' => 'api']);
app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

echo "--- Clearing old assignments ---\n";
$user = User::where('email', 'admin@example.com')->first();
DB::table('model_has_roles')->where('model_id', $user->id)->delete();
DB::table('model_has_permissions')->where('model_id', $user->id)->delete();

echo "--- Reassigning Permissions to Superadmin Role ---\n";
$role = Role::where('name', 'superadmin')->orWhere('name', 'admin')->first();
if (!$role) {
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
}

$permissions = Permission::all();
$role->syncPermissions($permissions);

echo "--- Reassigning Role to Admin User ---\n";
$user->assignRole($role);

echo "--- Refreshing User ---\n";
$user->refresh();
app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

$abilities = \App\Modules\Auth\Services\CaslAbilityConverter::toCaslAbilities($user->getAllPermissions()->pluck('name')->toArray());
echo "Final CASL Abilities Count: " . count($abilities) . "\n";
if (count($abilities) > 0) {
    echo "First ability: " . json_encode($abilities[0]) . "\n";
}
