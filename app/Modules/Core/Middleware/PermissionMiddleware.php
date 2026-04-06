<?php

namespace App\Modules\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Guard;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission, $guard = null)
    {
        $authGuard = Auth::guard($guard);
        $user = $authGuard->user();

        if (! $user && $request->bearerToken() && config('permission.use_passport_client_credentials')) {
            $user = Guard::getPassportClient($guard);
        }

        if (! $user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (! method_exists($user, 'hasPermissionTo')) {
            throw UnauthorizedException::missingTraitHasRoles($user);
        }

        $permissions = explode('|', self::parsePermissionsToString($permission));
        $guards = array_values(array_unique(array_filter([
            $guard,
            config('auth.defaults.guard'),
            'web',
            'api',
        ])));

        foreach ($guards as $guardName) {
            foreach ($permissions as $permissionName) {
                try {
                    if ($user->hasPermissionTo($permissionName, $guardName)) {
                        return $next($request);
                    }
                }
                catch (PermissionDoesNotExist $exception) {
                    continue;
                }
            }
        }

        throw UnauthorizedException::forPermissions($permissions);
    }

    public static function using($permission, $guard = null)
    {
        $permissionString = self::parsePermissionsToString($permission);
        $args = is_null($guard) ? $permissionString : $permissionString.','.$guard;

        return static::class.':'.$args;
    }

    protected static function parsePermissionsToString($permission)
    {
        if (is_array($permission)) {
            return implode('|', array_map(function ($item) {
                return (string) $item;
            }, $permission));
        }

        return (string) $permission;
    }
}
