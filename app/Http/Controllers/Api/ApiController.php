<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Info(
    title: 'KPI Laravel API',
    version: '1.0.0',
    description: 'API untuk sistem manajemen KPI karyawan',
    contact: new OA\Contact(email: 'admin@example.com')
)]
#[OA\Server(url: '/api', description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Masukkan token dari endpoint /auth/login'
)]
#[OA\Tag(name: 'Auth', description: 'Autentikasi')]
#[OA\Tag(name: 'Department', description: 'Manajemen Departemen')]
#[OA\Tag(name: 'Position', description: 'Manajemen Jabatan')]
#[OA\Tag(name: 'Employee', description: 'Manajemen Pegawai')]
#[OA\Tag(name: 'KPI Indicator', description: 'Indikator KPI')]
#[OA\Tag(name: 'KPI Component', description: 'Komponen KPI')]
#[OA\Tag(name: 'KPI Management', description: 'Input & Manajemen KPI')]
#[OA\Tag(name: 'KPI Report', description: 'Laporan KPI')]
#[OA\Tag(name: 'Task', description: 'Penugasan & Pekerjaan')]
#[OA\Tag(name: 'Notification', description: 'Notifikasi')]
#[OA\Tag(name: 'Analytics', description: 'Analitik & Statistik')]
class ApiController extends Controller
{
    protected function success(mixed $data = null, string $message = 'Berhasil', int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    protected function error(string $message, array $errors = [], int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function paginated(ResourceCollection $collection, LengthAwarePaginator $paginator, string $message = 'Berhasil'): JsonResponse
    {
        return $this->success([
            'items' => $collection->resolve(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], $message);
    }

    protected function resource(JsonResource $resource, string $message = 'Berhasil', int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->success($resource->resolve(), $message, $status);
    }
}
