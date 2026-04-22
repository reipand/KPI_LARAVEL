<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
<<<<<<< HEAD
use Illuminate\Support\Str;
=======
>>>>>>> 3de153c416c7b43a6b38779657b4c198503ef3cc
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends ApiController
{
    #[OA\Post(
<<<<<<< HEAD
        path: '/api/auth/login',
        summary: 'Login with NIP and Nama',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nip', 'nama'],
                properties: [
                    new OA\Property(property: 'nip',         type: 'string', example: 'EMP-001'),
                    new OA\Property(property: 'nama',        type: 'string', example: 'Budi Santoso'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'Chrome/Windows'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful — returns bearer token and user'),
            new OA\Response(response: 422, description: 'Invalid NIP or Nama'),
            new OA\Response(response: 429, description: 'Too many attempts'),
=======
        path: '/auth/login',
        summary: 'Login pegawai',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nip', 'nama'],
            properties: [
                new OA\Property(property: 'nip', type: 'string', example: '198001012010011001'),
                new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                new OA\Property(property: 'device_name', type: 'string', example: 'Chrome'),
            ]
        )),
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Login berhasil — token dikembalikan'),
            new OA\Response(response: 422, description: 'NIP atau nama tidak valid'),
            new OA\Response(response: 429, description: 'Terlalu banyak percobaan'),
>>>>>>> 3de153c416c7b43a6b38779657b4c198503ef3cc
        ]
    )]
    public function login(LoginRequest $request)
    {
        $key = 'api-login:'.$request->ip();
        $nip = $this->normalizeLoginText($request->string('nip')->toString());
        $nama = $this->normalizeLoginText($request->string('nama')->toString());

        $user = User::withoutGlobalScopes()
            ->whereRaw('LOWER(TRIM(nip)) = ?', [$nip])
            ->whereRaw('LOWER(TRIM(nama)) = ?', [$nama])
            ->first();

        if (!$user) {
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return $this->error(
                    'Terlalu banyak percobaan login. Coba lagi setelah 15 menit.',
                    ['retry_after' => RateLimiter::availableIn($key)],
                    Response::HTTP_TOO_MANY_REQUESTS
                );
            }

            RateLimiter::hit($key, 900);

            return $this->error(
                'NIP atau nama tidak valid.',
                ['attempts_left' => max(0, 5 - RateLimiter::attempts($key))],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        RateLimiter::clear($key);

        $expirationMinutes = (int) config('sanctum.expiration', 480);
        $cutoff = now()->subMinutes($expirationMinutes);

        $user->tokens()->where('created_at', '<', $cutoff)->delete();

        $activeTokens = $user->tokens()->orderBy('created_at')->get();
        $overflow = max(0, $activeTokens->count() - 2);

        if ($overflow > 0) {
            $activeTokens->take($overflow)->each->delete();
        }

        $deviceName = $request->input('device_name') ?: substr((string) $request->userAgent(), 0, 100) ?: 'unknown-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        ActivityLog::record(
            $user,
            'auth.login',
            User::class,
            $user->id,
            ['device_name' => $deviceName],
            $request
        );

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addMinutes($expirationMinutes)->toISOString(),
            'user' => (new UserResource($user))->resolve(),
        ], 'Login berhasil.');
    }

<<<<<<< HEAD
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout and revoke current token',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logout successful'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
=======
    #[OA\Post(path: '/auth/logout', summary: 'Logout', tags: ['Auth'], security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Logout berhasil')]
>>>>>>> 3de153c416c7b43a6b38779657b4c198503ef3cc
    )]
    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()?->currentAccessToken()?->delete();

        ActivityLog::record(
            $user,
            'auth.logout',
            User::class,
            $user?->id,
            [],
            $request
        );

        return $this->success(null, 'Logout berhasil.');
    }

<<<<<<< HEAD
    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Get authenticated user profile',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Current user data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
=======
    #[OA\Get(path: '/auth/me', summary: 'Data user yang sedang login', tags: ['Auth'], security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'OK')]
>>>>>>> 3de153c416c7b43a6b38779657b4c198503ef3cc
    )]
    public function me(Request $request)
    {
        return $this->resource(new UserResource($request->user()));
    }

    private function normalizeLoginText(?string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?: '';

        return Str::lower($value);
    }
}
