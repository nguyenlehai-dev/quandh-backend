<?php

namespace App\Modules\Meeting\Notifications;

use App\Modules\Meeting\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Thông báo khi cuộc họp kết thúc.
 * Gửi tổng hợp kết luận và kết quả biểu quyết cho tất cả đại biểu.
 */
class MeetingCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Meeting $meeting,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $conclusionCount = $this->meeting->conclusions()->count();

        return (new MailMessage)
            ->subject('Kết quả cuộc họp: '.$this->meeting->title)
            ->greeting('Xin chào '.$notifiable->name.'!')
            ->line('Cuộc họp **'.$this->meeting->title.'** đã kết thúc.')
            ->line('Số kết luận: '.$conclusionCount)
            ->action('Xem kết luận & tài liệu', url('/meetings/'.$this->meeting->id))
            ->line('Bạn có thể truy cập lại để xem danh sách kết luận và ghi chú cá nhân.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'meeting_completed',
            'meeting_id' => $this->meeting->id,
            'title' => $this->meeting->title,
            'message' => 'Cuộc họp "'.$this->meeting->title.'" đã kết thúc. Xem kết luận.',
        ];
    }
}
