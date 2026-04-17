<?php

namespace App\Http\Controllers\Api;

use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device_type' => ['nullable', 'in:web,android,ios'],
        ]);

        FcmToken::updateOrCreate(
            ['user_id' => $request->user()->id, 'token' => $data['token']],
            ['device_type' => $data['device_type'] ?? 'web'],
        );

        return $this->success(null, 'FCM token disimpan.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $request->user()
            ->fcmTokens()
            ->where('token', $data['token'])
            ->delete();

        return $this->success(null, 'FCM token dihapus.');
    }
}
