<?php
namespace App\Modules\Core\Models;
use Illuminate\Support\Facades\DB;

$user = User::where('email', 'admin@example.com')->first();
$permissions = Permission::all();

echo "Forcibly giving " . $permissions->count() . " permissions...\n";

// Raw insert to avoid spatie duplicated key errors
$inserts = [];
foreach ($permissions as $p) {
    // Spatie checks the "guard" of the permission. If they were created with 'api', we need 'web' and vice versa.
    // Let's just insert for whatever ID.
    $inserts[] = [
        'permission_id' => $p->id,
        'model_type' => 'App\Modules\Core\Models\User',
        'model_id' => $user->id
    ];
}

DB::table('model_has_permissions')->insertOrIgnore($inserts);
echo "Permissions inserted directly to model_has_permissions!\n";

// Clear cache
app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

$abilities = \App\Modules\Auth\Services\CaslAbilityConverter::toCaslAbilities($user->getAllPermissions()->pluck('name')->toArray());
echo "Final CASL Abilities Count: " . count($abilities) . "\n";
