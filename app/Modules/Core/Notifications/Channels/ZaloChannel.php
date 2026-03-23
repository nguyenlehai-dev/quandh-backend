<?php

namespace App\Modules\Core\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Custom Laravel Notification Channel cho Zalo OA.
 * Gửi tin nhắn qua Zalo Official Account API.
 */
class ZaloChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        // Notification phải implement phương thức toZalo()
        if (! method_exists($notification, 'toZalo')) {
            return;
        }

        $data = $notification->toZalo($notifiable);
        $zaloUserId = $data['zalo_user_id'] ?? null;

        if (! $zaloUserId) {
            return;
        }

        try {
            // TODO: Cấu hình Zalo OA access_token
            // Http::withToken(config('services.zalo.access_token'))
            //     ->post('https://openapi.zalo.me/v3.0/oa/message/cs', [
            //         'recipient' => ['user_id' => $zaloUserId],
            //         'message' => ['text' => $data['message'] ?? ''],
            //     ]);

            Log::info('Zalo OA sent', [
                'user_id' => $notifiable->id,
                'zalo_user_id' => $zaloUserId,
                'message' => $data['message'] ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('Zalo OA failed', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
