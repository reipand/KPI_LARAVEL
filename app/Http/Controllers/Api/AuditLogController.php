<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    #[OA\Get(
        path: '/api/v2/audit-logs',
        summary: 'List audit logs with filters',
        security: [['bearerAuth' => []]],
        tags: ['Audit Logs'],
        parameters: [
            new OA\Parameter(name: 'module_name',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'action_name',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date_from',    in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to',      in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated audit log list'),
        ]
    )]
        public function index(Request $request): JsonResponse
    {
        $tenantId = app()->bound('bypass_tenant_scope') && app('bypass_tenant_scope')
            ? $request->tenant_id
            : (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);

        $logs = AuditLog::with('user')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($request->module_name, fn ($q) => $q->where('module_name', $request->module_name))
            ->when($request->action_name, fn ($q) => $q->where('action_name', $request->action_name))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->date_from, fn ($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->where('created_at', '<=', $request->date_to.' 23:59:59'))
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($logs);
    }
}
