<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that route-bound model instances belong to the current tenant.
 * Prevents cross-tenant access via ID tampering (IDOR attacks).
 *
 * Usage: ->middleware('tenant.owns')
 */
class EnsureTenantOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('bypass_tenant_scope') && app('bypass_tenant_scope') === true) {
            return $next($request);
        }

        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;

        if ($tenantId === null) {
            return $next($request);
        }

        foreach ($request->route()->parameters() as $model) {
            if (! ($model instanceof Model)) {
                continue;
            }

            if (isset($model->tenant_id) && (int) $model->tenant_id !== (int) $tenantId) {
                return response()->json(['message' => 'Access denied to this resource.'], 403);
            }
        }

        return $next($request);
    }
}
