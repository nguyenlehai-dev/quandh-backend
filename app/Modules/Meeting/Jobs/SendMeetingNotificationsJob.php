<?php

namespace App\Modules\Meeting\Jobs;

use App\Modules\Core\Services\NotificationService;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Notifications\MeetingActivatedNotification;
use App\Modules\Meeting\Notifications\MeetingCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job gửi thông báo hàng loạt cho tất cả đại biểu của cuộc họp.
 * Sử dụng NotificationService từ Core module (dùng chung toàn hệ thống).
 */
class SendMeetingNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Meeting $meeting,
        public string $notificationType,
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        // Lấy tất cả users là thành viên cuộc họp
        $users = $this->meeting->users;

        if ($users->isEmpty()) {
            return;
        }

        $notification = match ($this->notificationType) {
            'activated' => new MeetingActivatedNotification($this->meeting),
            'completed' => new MeetingCompletedNotification($this->meeting),
            default => null,
        };

        if ($notification) {
            // Dùng Core NotificationService (Email + Database)
            $notificationService->send($users, $notification);

            // Firebase Push cho tất cả đại biểu
            $notificationService->sendFirebaseToMany(
                $users,
                $this->meeting->title,
                $this->notificationType === 'activated'
                    ? 'Bạn được mời tham gia cuộc họp: '.$this->meeting->title
                    : 'Cuộc họp "'.$this->meeting->title.'" đã kết thúc.'
            );
        }
    }
}
