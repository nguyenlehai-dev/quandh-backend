# Phân tích Bổ sung: Module Meeting - Real-time Broadcasting & Notifications (Job/Queue)

**Ngày tạo:** 2026-03-23  
**Mục đích:** Ghi lại phân tích giải pháp kỹ thuật tích hợp sự kiện thời gian thực (Real-time Broadcast) và hệ thống gửi thông báo tự động (via Core NotificationService) trong tiến trình Cuộc họp không giấy.

---

## 1. Tích hợp Real-time Broadcasting

Trong một cuộc họp trực tiếp, các thành viên cần thấy ngay lập tức khi Chủ tọa chuyển sang mục nghị sự mới hoặc mở phiên biểu quyết mà **không cần tải lại trang (F5)**.

### 1.1 Các sự kiện (Events) được tạo

Sử dụng cơ chế `ShouldBroadcast` của Laravel qua các kênh `PrivateChannel`.
1. **`MeetingAgendaChanged`**: 
   - **Trigger:** Khi gọi API `PATCH /agendas/{id}/set-active`.
   - **Payload:** ID mục nghị sự đang active.
   - **Ứng dụng:** Frontend VueJS tự động scroll đến nội dung và highlight mục nghị sự mới.
2. **`MeetingVotingStatusChanged`**:
   - **Trigger:** Mở (`open`) hoặc Đóng (`close`) form biểu quyết.
   - **Ứng dụng:** Frontend tự động popup màn hình Bỏ Phiếu hoặc tự động đóng form khóa kết quả.
3. **`MeetingStatusChanged`**:
   - **Trigger:** Thay đổi trạng thái cuộc họp thành Bắt đầu / Kết thúc.

### 1.2 Phân quyền Broadcasting (Authorization)

- **Channel Name:** `private-meeting.{meeting_id}`
- **Security:** Trong `routes/channels.php`, chỉ những User có ID thuộc bảng `m_participants` của Meeting đó mới được phép Subscribe (lắng nghe websockets).

---

## 2. Gửi Thông Báo Tự Động (Queue & Job)

Việc gửi thông báo tới hàng chục đại biểu tốn thời gian (kết nối SMTP, Firebase API), nếu gọi trực tiếp sẽ gây "treo" API đổi trạng thái.

### 2.1 Jobs (Backgroud Processing)

- **Job:** `App\Modules\Meeting\Jobs\SendMeetingNotificationsJob`
- **Cơ chế:** Kế thừa `ShouldQueue`. API chỉ mất `0.01s` đẩy Job vào Redis/Database queue và return `200 OK` về Frontend ngay lập tức.
- Worker (`php artisan queue:work`) sẽ chạy ngầm để gửi thư.

### 2.2 Notifications & Tiêu thụ Core Service

- **Sử dụng:** `MeetingActivatedNotification` và `MeetingCompletedNotification`.
- **Luồng hoạt động:** 
  1. Thay vì Meeting Module tự quản lý kênh gửi, `SendMeetingNotificationsJob` sẽ Instantiate (Khởi tạo) `App\Modules\Core\Services\NotificationService`.
  2. Truyền tham số User List vào Core Service: `$notificationService->send($users, $notification);`
  3. Gọi Firebase Push: `$notificationService->sendFirebaseToMany(..., 'Họp bắt đầu!', ...)`
- **Lợi ích:** Bám sát tư duy kiến trúc "Chức năng chung nằm ở Core". Meeting module không cần biết chi tiết kết nối API Zalo / Firebase; nó chỉ cần ra lệnh cho Core.

---

## 3. Cấu hình Frontend (Vue 3 Latch)

- **Yêu cầu:** Frontend cài `laravel-echo` và `pusher-js`.
- **Code mẫu kết nối WebSocket:**
```javascript
window.Echo.private(`meeting.${meetingId}`)
    .listen('MeetingAgendaChanged', (e) => {
        console.log('Chuyển sang mục Agenda ID: ', e.agenda_id);
    });
```
- Module Meeting ở Backend đã chuẩn bị hoàn thiện payload JSON đẩy theo đúng spec dành cho quá trình triển khai Vue.js sắp tới.
