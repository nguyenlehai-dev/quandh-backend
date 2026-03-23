<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\User;
use App\Modules\Core\Models\UserFcmToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * NotificationService — Dùng chung cho toàn hệ thống.
 *
 * Hỗ trợ gửi thông báo qua nhiều kênh:
 * - Email (Laravel Mail)
 * - Database (Laravel Notification)
 * - Firebase (FCM Push)
 * - SMS (Vonage/Twilio)
 * - Zalo OA
 *
 * Tất cả đều chạy qua Queue để không gây nghẽn API.
 */
class NotificationService
{
    /**
     * Gửi notification đến danh sách users.
     *
     * @param  Collection|User[]  $users     Danh sách users
     * @param  Notification       $notification  Notification instance
     */
    public function send(Collection $users, Notification $notification): void
    {
        if ($users->isEmpty()) {
            return;
        }

        NotificationFacade::send($users, $notification);
    }

    /**
     * Gửi notification đến 1 user.
     */
    public function sendToUser(User $user, Notification $notification): void
    {
        $user->notify($notification);
    }

    /**
     * Gửi Firebase Push Notification đến user (tất cả thiết bị).
     *
     * @param  User    $user    User cần gửi
     * @param  string  $title   Tiêu đề notification
     * @param  string  $body    Nội dung notification
     * @param  array   $data    Dữ liệu bổ sung
     */
    public function sendFirebase(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = UserFcmToken::where('user_id', $user->id)->pluck('token')->toArray();

        if (empty($tokens)) {
            return;
        }

        // TODO: Tích hợp Firebase Admin SDK hoặc package laravel-notification-channels/fcm
        // Ví dụ sử dụng HTTP API v1:
        // foreach ($tokens as $token) {
        //     Http::withToken($serviceAccountToken)
        //         ->post('https://fcm.googleapis.com/v1/projects/{project}/messages:send', [
        //             'message' => [
        //                 'token' => $token,
        //                 'notification' => ['title' => $title, 'body' => $body],
        //                 'data' => $data,
        //             ],
        //         ]);
        // }

        Log::info('Firebase Push', [
            'user_id' => $user->id,
            'tokens_count' => count($tokens),
            'title' => $title,
        ]);
    }

    /**
     * Gửi Firebase Push đến nhiều users.
     */
    public function sendFirebaseToMany(Collection $users, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->sendFirebase($user, $title, $body, $data);
        }
    }

    /**
     * Gửi SMS.
     *
     * @param  string  $phone    Số điện thoại
     * @param  string  $message  Nội dung tin nhắn
     */
    public function sendSms(string $phone, string $message): void
    {
        // TODO: Tích hợp Vonage/Twilio/SpeedSMS
        // Vonage::message()->send([
        //     'to' => $phone,
        //     'from' => config('services.vonage.from'),
        //     'text' => $message,
        // ]);

        Log::info('SMS sent', ['phone' => $phone, 'message' => $message]);
    }

    /**
     * Gửi tin nhắn Zalo OA.
     *
     * @param  string  $zaloUserId  Zalo User ID (từ OA)
     * @param  string  $message     Nội dung tin nhắn
     * @param  array   $templateData  Dữ liệu template (nếu dùng ZNS)
     */
    public function sendZalo(string $zaloUserId, string $message, array $templateData = []): void
    {
        // TODO: Tích hợp Zalo OA API
        // Http::withToken(config('services.zalo.access_token'))
        //     ->post('https://openapi.zalo.me/v3.0/oa/message/cs', [
        //         'recipient' => ['user_id' => $zaloUserId],
        //         'message' => ['text' => $message],
        //     ]);

        Log::info('Zalo OA sent', ['zalo_user_id' => $zaloUserId, 'message' => $message]);
    }

    /**
     * Gửi thông báo qua tất cả kênh cho 1 user.
     */
    public function sendAll(User $user, Notification $notification, string $firebaseTitle = '', string $firebaseBody = ''): void
    {
        // Email + Database qua Laravel Notification
        $user->notify($notification);

        // Firebase Push (nếu có title)
        if ($firebaseTitle) {
            $this->sendFirebase($user, $firebaseTitle, $firebaseBody);
        }
    }
}
