<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct(private readonly Messaging $messaging) {}

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($title, $body))
            ->withData(array_map('strval', $data));

        try {
            $report = $this->messaging->sendMulticast($message, $tokens);

            if ($report->hasFailures()) {
                $invalidTokens = $report->invalidTokens();
                if (!empty($invalidTokens)) {
                    $user->fcmTokens()
                        ->whereIn('token', $invalidTokens)
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
