<?php
use Illuminate\Support\Facades\DB;

// Update all permissions and roles to 'api' guard so the API tokens can use them
DB::table('permissions')->update(['guard_name' => 'api']);
DB::table('roles')->update(['guard_name' => 'api']);
app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

echo "Guards updated to 'api' and cache flushed.\n";

$user = \App\Modules\Core\Models\User::where('email', 'admin@example.com')->first();
$abilities = \App\Modules\Auth\Services\CaslAbilityConverter::toCaslAbilities($user->getAllPermissions()->pluck('name')->toArray());
echo "Final CASL Abilities Count: " . count($abilities) . "\n";
