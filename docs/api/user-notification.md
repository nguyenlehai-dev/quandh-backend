# API Thông báo người dùng (User Notifications) – Core

Quản lý cấu hình thông báo và danh sách thông báo của người dùng hiện tại.

**Base path:** `/api/user`

---

## Lấy cấu hình thông báo

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/user/notification-preferences` |
| **Response** | `{ "notify_email": true, "notify_system": true, "notify_meeting_reminder": true, "notify_vote": true, "notify_document": false }` |

---

## Cập nhật cấu hình thông báo

| | |
|---|---|
| **Method** | PUT |
| **Path** | `/api/user/notification-preferences` |
| **Body** | `notify_email`, `notify_system`, `notify_meeting_reminder`, `notify_vote`, `notify_document` (đều là boolean, optional). |
| **Response** | `{ "message": "Cập nhật cấu hình thông báo thành công." }` |

---

## Danh sách thông báo gần đây

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/user/notifications` |
| **Response** | `{ "data": [...], "unread_count": 3 }` với mỗi item gồm `id`, `title`, `subtitle`, `icon`, `color`, `time`, `isSeen`. |

---

## Đánh dấu đã đọc

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/user/notifications/mark-read` |
| **Body** | `ids` (array) – danh sách ID notification. |
| **Response** | `{ "success": true }` |

---

## Đánh dấu tất cả đã đọc

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/user/notifications/mark-all-read` |
| **Response** | `{ "success": true }` |

---

## Đánh dấu chưa đọc

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/user/notifications/mark-unread` |
| **Body** | `ids` (array) – danh sách ID notification. |
| **Response** | `{ "success": true }` |

---

## Xóa một thông báo

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/user/notifications/{id}` |
| **UrlParam** | `id` – ID notification. |
| **Response** | `{ "success": true }` |
