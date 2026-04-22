<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    public function index(Request $request): JsonResponse
    {
        $users = User::with(['roles', 'department', 'positionRef'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('nip', 'like', "%{$request->search}%");
            }))
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)))
            ->orderBy('nama')
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;

        $data = $request->validate([
            'nip'             => 'required|string|max:50|unique:users,nip',
            'nama'            => 'required|string|max:255',
            'email'           => 'nullable|email|unique:users,email',
            'jabatan'         => 'required|string|max:100',
            'departemen'      => 'required|string|max:100',
            'department_id'   => 'nullable|exists:departments,id',
            'position_id'     => 'nullable|exists:positions,id',
            'status_karyawan' => 'required|string|max:50',
            'tanggal_masuk'   => 'required|date',
            'no_hp'           => 'nullable|string|max:20',
            'role'            => 'required|string|exists:' . config('permission.table_names.roles', 'roles') . ',name',
            'password'        => 'required|string|min:8',
        ]);

        $user = User::create([
            ...$data,
            'tenant_id' => $tenantId,
            'password'  => Hash::make($data['password']),
        ]);

        $user->syncRoles([$data['role']]);

        $this->auditService->log('users', 'create', 'User', $user->id, null, [
            'nip'  => $user->nip,
            'nama' => $user->nama,
            'role' => $data['role'],
        ]);

        return response()->json(['message' => 'User created.', 'data' => $user->load('roles')], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $user->load(['roles', 'department', 'positionRef'])]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->checkTenantOwnership($user);

        $data = $request->validate([
            'nama'            => 'sometimes|string|max:255',
            'email'           => 'nullable|email|unique:users,email,'.$user->id,
            'jabatan'         => 'sometimes|string|max:100',
            'departemen'      => 'sometimes|string|max:100',
            'department_id'   => 'nullable|exists:departments,id',
            'position_id'     => 'nullable|exists:positions,id',
            'status_karyawan' => 'sometimes|string|max:50',
            'no_hp'           => 'nullable|string|max:20',
            'role'            => 'sometimes|string',
            'password'        => 'nullable|string|min:8',
        ]);

        $old = $user->only(['nama', 'email', 'jabatan', 'departemen', 'status_karyawan']);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $user->update($data);

        $this->auditService->log('users', 'update', 'User', $user->id, $old, $user->fresh()->only(['nama', 'email', 'jabatan']));

        return response()->json(['message' => 'User updated.', 'data' => $user->load('roles')]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->checkTenantOwnership($user);

        $old = $user->only(['nip', 'nama', 'email']);
        $user->delete();

        $this->auditService->log('users', 'delete', 'User', $user->id, $old, null);

        return response()->json(['message' => 'User deleted.']);
    }

    public function roles(): JsonResponse
    {
        $roles = Role::whereNotIn('name', ['super_admin'])->get(['id', 'name']);

        return response()->json(['data' => $roles]);
    }

    private function checkTenantOwnership(User $user): void
    {
        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;

        if ($tenantId !== null && (int) $user->tenant_id !== (int) $tenantId) {
            abort(403, 'Access denied to this user.');
        }
    }
}
