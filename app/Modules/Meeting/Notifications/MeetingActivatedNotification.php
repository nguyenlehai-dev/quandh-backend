<?php

namespace App\Modules\Meeting\Notifications;

use App\Modules\Meeting\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Thông báo khi cuộc họp được kích hoạt.
 * Gửi qua Email + Database (và có thể mở rộng thêm SMS, Firebase).
 */
class MeetingActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Meeting $meeting,
    ) {}

    /** Kênh gửi thông báo. */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Gửi email nếu user có email
        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        // Mở rộng: thêm 'firebase', 'vonage' (SMS) tùy cấu hình
        // if ($notifiable->fcm_token) {
        //     $channels[] = FcmChannel::class;
        // }

        return $channels;
    }

    /** Nội dung email. */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Thông báo cuộc họp: '.$this->meeting->title)
            ->greeting('Xin chào '.$notifiable->name.'!')
            ->line('Bạn được mời tham gia cuộc họp:')
            ->line('**'.$this->meeting->title.'**')
            ->line('Địa điểm: '.($this->meeting->location ?? 'Chưa xác định'))
            ->line('Thời gian: '.$this->meeting->start_at?->format('d/m/Y H:i').' - '.$this->meeting->end_at?->format('d/m/Y H:i'))
            ->line($this->meeting->description ?? '')
            ->action('Xem chi tiết cuộc họp', url('/meetings/'.$this->meeting->id))
            ->line('Vui lòng xem tài liệu và chuẩn bị trước cuộc họp.');
    }

    /** Dữ liệu lưu vào bảng notifications (database channel). */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'meeting_activated',
            'meeting_id' => $this->meeting->id,
            'title' => $this->meeting->title,
            'location' => $this->meeting->location,
            'start_at' => $this->meeting->start_at?->toIso8601String(),
            'end_at' => $this->meeting->end_at?->toIso8601String(),
            'message' => 'Bạn được mời tham gia cuộc họp: '.$this->meeting->title,
        ];
    }
}
