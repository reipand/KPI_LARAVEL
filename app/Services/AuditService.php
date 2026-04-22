<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        string $module,
        string $action,
        string $entity,
        int|null $entityId = null,
        array|null $oldValues = null,
        array|null $newValues = null
    ): void {
        $user = Auth::user();

        AuditLog::create([
            'tenant_id'      => $user?->tenant_id ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null),
            'user_id'        => $user?->id,
            'module_name'    => $module,
            'action_name'    => $action,
            'entity_name'    => $entity,
            'entity_id'      => $entityId,
            'old_value_json' => $oldValues,
            'new_value_json' => $newValues,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'created_at'     => now(),
        ]);
    }
}
