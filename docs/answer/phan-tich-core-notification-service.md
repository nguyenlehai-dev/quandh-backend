# Phân tích Kiến trúc: Core Notification Service & Quản lý FCM Token đa thiết bị

**Ngày tạo:** 2026-03-23  
**Mục đích:** Ghi lại phân tích và giải pháp kỹ thuật di dời logic thông báo vào Core Module, hỗ trợ Push Notification qua Firebase (FCM) và Zalo OA cho toàn hệ thống.

---

## 1. Tổng quan thiết kế

Việc gửi thông báo là tác vụ dùng chung cho nhiều module (Meeting, Post, Document...). Kiến trúc mới quy tụ logic này về một Service duy nhất trong `App\Modules\Core`.

### 1.1 Quản lý FCM Token (đa thiết bị)

- **Migration bổ sung:** `2026_03_23_100000_create_user_fcm_tokens_table.php` (tạo bảng `user_fcm_tokens`). Cắt bỏ cột `fcm_token` lỗi thời trong DB `users`.
- **Bảng:** `user_fcm_tokens` (id, user_id, token, device_type, device_id, timestamps).
- **Model:** `App\Modules\Core\Models\UserFcmToken`
- **Quan hệ (Relationship):** `App\Modules\Core\Models\User` bổ sung hàm `fcmTokens()` — Quan hệ 1:N cho phép một nhân viên sử dụng hệ thống và nhận Push trên nhiều máy tính/điện thoại cùng lúc.

### 1.2 Core Notification Service

- **File:** `App\Modules\Core\Services\NotificationService.php`
- **Chức năng:** Class Helper đóng gói thao tác sử dụng `Notification::send()`.
- **Ưu điểm:** Các module khác khi cần gửi mail, sms, zalo chỉ cần dùng DI (Dependency Injection) tiêm `NotificationService` vào Job/Controller mà không cần lo về vòng lặp Users.

---

## 2. Các Custom Channel Notification

Hệ thống tận dụng tính năng Notification Channels tích hợp sẵn của Laravel framework.

### 2.1 Firebase Channel (Push Notification)

- **File:** `App\Modules\Core\Notifications\Channels\FirebaseChannel.php`
- **Cách hoạt động:** 
  1. Đọc quan hệ `fcmTokens` của đối tượng (Group/Users) đang gửi tới.
  2. Nén các token vào các `chunk(500)` nhằm tránh giới hạn Payload Limit của Firebase API.
  3. Gửi batch request thông qua Firebase Admin SDK / Kreait (TODO trong Channel khi có config thực tế).

### 2.2 Zalo Channel

- **File:** `App\Modules\Core\Notifications\Channels\ZaloChannel.php`
- **Cách hoạt động:** 
  Dành để nhắn tin quan Zalo Official Account App hoặc cổng ZNS. Chuẩn hóa format payload để Zalo API dễ dàng đọc và phân phối.

---

## 3. Ứng dụng & Hướng dẫn sử dụng

Hệ thống nay đã sẵn sàng cho bất kỳ Notification Class nào:

```php
// Trong class MeetingActivatedNotification extends \Illuminate\Notifications\Notification

public function via(object $notifiable): array
{
    // Cấu hình linh hoạt kênh gửi tuỳ thuộc vào event
    return ['mail', 'database', FirebaseChannel::class, ZaloChannel::class];
}

public function toFirebase(object $notifiable)
{
    // Channel sẽ tự động check method này giống hệt toMail, toDatabase
    return [
        'title' => 'Trạng thái cuộc họp thay đổi',
        'body' => 'Cuộc họp Hội Đồng Quản Trị bắt đầu'
    ];
}
```

Với kiến trúc này, toàn bộ Core Project hoàn toàn decouple phần thông báo khỏi các domain business (Meeting, Task), chuẩn kiến trúc Separation of Concerns (SoC).
