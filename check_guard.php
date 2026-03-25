<?php
use Illuminate\Support\Facades\DB;
$permsGuard = DB::table('permissions')->value('guard_name');
$roleGuard = DB::table('roles')->where('name', 'admin')->value('guard_name');
echo "Permissions Guard: $permsGuard\n";
echo "Admin Role Guard: $roleGuard\n";
