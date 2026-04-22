<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that limits all queries to the current request tenant.
 * Super Admins bypass this scope so they can access cross-tenant data.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Super admin bypasses tenant isolation
        if (app()->bound('bypass_tenant_scope') && app('bypass_tenant_scope') === true) {
            return;
        }

        if (app()->bound('current_tenant_id')) {
            $tenantId = app('current_tenant_id');
            if ($tenantId !== null) {
                $builder->where($model->getTable().'.tenant_id', $tenantId);
            }
        }
    }
}
