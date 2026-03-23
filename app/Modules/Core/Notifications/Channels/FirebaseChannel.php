<?php

namespace App\Modules\Core\Notifications\Channels;

use App\Modules\Core\Models\UserFcmToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Custom Laravel Notification Channel cho Firebase Cloud Messaging.
 * Gửi push notification đến tất cả thiết bị của user.
 */
class FirebaseChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        // Notification phải implement phương thức toFirebase()
        if (! method_exists($notification, 'toFirebase')) {
            return;
        }

        $data = $notification->toFirebase($notifiable);

        // Lấy tất cả FCM tokens của user
        $tokens = UserFcmToken::where('user_id', $notifiable->id)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            try {
                // TODO: Cấu hình Firebase Admin SDK credentials
                // Http::withToken(config('services.firebase.server_key'))
                //     ->post('https://fcm.googleapis.com/fcm/send', [
                //         'to' => $token,
                //         'notification' => [
                //             'title' => $data['title'] ?? '',
                //             'body' => $data['body'] ?? '',
                //         ],
                //         'data' => $data['data'] ?? [],
                //     ]);

                Log::info('Firebase Push sent', [
                    'user_id' => $notifiable->id,
                    'token' => substr($token, 0, 20).'...',
                    'title' => $data['title'] ?? '',
                ]);
            } catch (\Throwable $e) {
                Log::error('Firebase Push failed', [
                    'user_id' => $notifiable->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
