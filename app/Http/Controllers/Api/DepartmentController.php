<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreDepartmentRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $departments = Department::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('nama')
            ->get();

        return $this->success($departments);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        ActivityLog::record($request->user(), 'create_department', 'Department', $department->id, ['nama' => $department->nama], $request);

        return $this->success($department, 'Departemen berhasil dibuat', Response::HTTP_CREATED);
    }

    public function update(StoreDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        ActivityLog::record($request->user(), 'update_department', 'Department', $department->id, ['nama' => $department->nama], $request);

        return $this->success($department->fresh(), 'Departemen berhasil diperbarui');
    }

    public function destroy(Request $request, Department $department): JsonResponse
    {
        $nama = $department->nama;
        $department->delete();

        ActivityLog::record($request->user(), 'delete_department', 'Department', null, ['nama' => $nama], $request);

        return $this->success(null, 'Departemen berhasil dihapus');
    }
}
