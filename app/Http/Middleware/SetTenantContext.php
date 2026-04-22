<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's tenant and binds it to the IoC container.
 * Must run AFTER auth:sanctum middleware.
 *
 * Super Admins bypass tenant scope so they can access cross-tenant data.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Super Admin role bypasses tenant isolation
        if ($user->hasRole('super_admin')) {
            app()->instance('bypass_tenant_scope', true);
            app()->instance('current_tenant_id', null);

            return $next($request);
        }

        if (empty($user->tenant_id)) {
            return response()->json(['message' => 'User has no tenant assigned.'], 403);
        }

        $tenant = \App\Models\Tenant::withoutGlobalScopes()
            ->find($user->tenant_id);

        if (! $tenant || $tenant->status !== 'active') {
            return response()->json(['message' => 'Tenant is inactive or not found.'], 403);
        }

        app()->instance('current_tenant_id', $tenant->id);
        app()->instance('current_tenant', $tenant);
        app()->instance('bypass_tenant_scope', false);

        return $next($request);
    }
}
