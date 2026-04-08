<?php

namespace App\Modules\Core\Middleware;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRouteModelsBelongToOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $this->resolveCurrentOrganizationId();

        if ($organizationId === null) {
            return $next($request);
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            if (! $parameter->offsetExists('organization_id')) {
                continue;
            }

            if ((int) $parameter->getAttribute('organization_id') !== $organizationId) {
                throw (new ModelNotFoundException)->setModel($parameter::class, [$parameter->getKey()]);
            }
        }

        return $next($request);
    }

    private function resolveCurrentOrganizationId(): ?int
    {
        $organizationId = function_exists('getPermissionsTeamId') ? getPermissionsTeamId() : null;

        if (! is_numeric($organizationId) || (int) $organizationId <= 0) {
            return null;
        }

        return (int) $organizationId;
    }
}
