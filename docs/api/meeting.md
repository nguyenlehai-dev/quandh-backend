# API Cuộc họp (Meeting)

Quản lý cuộc họp không giấy: thống kê, danh sách, chi tiết, CRUD, thao tác hàng loạt, xuất/nhập Excel. Một cuộc họp bao gồm: thành viên, chương trình nghị sự, tài liệu, kết luận, ghi chú cá nhân, đăng ký phát biểu, biểu quyết.

**Base path:** `/api/meetings`

---

## Thống kê

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/meetings/stats` |
| **Query** | `search` (tiêu đề), `status` (draft \| active \| in_progress \| completed), `from_date`, `to_date`, `sort_by`, `sort_order`, `limit` (1-100). |
| **Response** | `{ "total": 10, "active": 5, "inactive": 5 }` — total (sau lọc), active = active, inactive = draft + in_progress + completed. |

---

## Danh sách cuộc họp

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/meetings` |
| **Query** | `search`, `status`, `from_date`, `to_date`, `sort_by` (id \| title \| start_at \| created_at), `sort_order` (asc \| desc), `limit` (1-100). |
| **Response** | Paginated collection; mỗi item có `participants_count`, `agendas_count`, `documents_count`, `conclusions_count`. |

---

## Chi tiết cuộc họp

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/meetings/{id}` |
| **UrlParam** | `id` — ID cuộc họp. |
| **Response** | Object cuộc họp kèm `participants`, `agendas`, `documents`, `conclusions`, `votings`. |

---

## Tạo cuộc họp

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/meetings` |
| **Body** | `title` (required), `description` (optional), `location` (optional), `start_at` (optional, datetime), `end_at` (optional, datetime, after start_at), `status` (required: draft \| active \| in_progress \| completed). |
| **Response** | 201, object cuộc họp + `"message": "Cuộc họp đã được tạo thành công!"`. |

---

## Cập nhật cuộc họp

| | |
|---|---|
| **Method** | PUT / PATCH |
| **Path** | `/api/meetings/{id}` |
| **Body** | Giống tạo (các field tùy chọn). |
| **Response** | Object cuộc họp đã cập nhật. |

---

## Xóa cuộc họp

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/meetings/{id}` |
| **Response** | `{ "message": "Cuộc họp đã được xóa thành công!" }`. |

---

## Xóa hàng loạt

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/meetings/bulk-delete` |
| **Body** | `ids` (array) — danh sách ID cuộc họp. |
| **Response** | `{ "message": "Đã xóa thành công các cuộc họp được chọn!" }`. |

---

## Cập nhật trạng thái hàng loạt

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/meetings/bulk-status` |
| **Body** | `ids` (array), `status` (required: draft \| active \| in_progress \| completed). |
| **Response** | `{ "message": "Cập nhật trạng thái thành công các cuộc họp được chọn!" }`. |

---

## Đổi trạng thái cuộc họp

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/meetings/{id}/status` |
| **Body** | `status` (required). |
| **Response** | Object cuộc họp + message. |

---

## Xuất Excel

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/meetings/export` |
| **Query** | Cùng bộ lọc với index. |
| **Response** | File `meetings.xlsx`. Cột: ID, Tiêu đề, Mô tả, Địa điểm, Bắt đầu, Kết thúc, Trạng thái, Số thành viên, Số mục nghị sự, Số kết luận, Người tạo, Người cập nhật, Ngày tạo, Ngày cập nhật. |

---

## Nhập Excel

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/meetings/import` |
| **Body** | `file` (required) — xlsx, xls, csv. Cột: title (bắt buộc), description, location, status. |
| **Response** | `{ "message": "Import cuộc họp thành công." }`. |

---

## Sub-resources

### Thành viên (`/api/meetings/{id}/participants`)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | `.../participants` | Danh sách thành viên |
| POST | `.../participants` | Gán thành viên (`user_id`, `position`, `meeting_role`) |
| PUT | `.../participants/{pid}` | Cập nhật vai trò/chức vụ |
| DELETE | `.../participants/{pid}` | Xóa thành viên |
| PATCH | `.../participants/{pid}/checkin` | Điểm danh (`attendance_status`, `absence_reason`) |

### Chương trình nghị sự (`/api/meetings/{id}/agendas`)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | `.../agendas` | Danh sách (sorted by order_index) |
| POST | `.../agendas` | Tạo mục (`title`, `description`, `order_index`, `duration`) |
| PUT | `.../agendas/{aid}` | Cập nhật |
| DELETE | `.../agendas/{aid}` | Xóa |
| PATCH | `.../agendas/reorder` | Sắp xếp lại (`ids[]`) |
| PATCH | `.../agendas/{aid}/set-active` | Đặt mục làm active hiện tại (Real-time Broadcast) |

### Tài liệu (`/api/meetings/{id}/documents`)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | `.../documents` | Danh sách tài liệu + files |
| POST | `.../documents` | Tạo + upload (`title`, `files[]`) |
| PUT | `.../documents/{did}` | Cập nhật + upload/remove files |
| DELETE | `.../documents/{did}` | Xóa |

### Kết luận (`/api/meetings/{id}/conclusions`)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | `.../conclusions` | Danh sách kết luận |
| POST | `.../conclusions` | Tạo (`title`, `content`, `meeting_agenda_id`) |
| PUT | `.../conclusions/{cid}` | Cập nhật |
| DELETE | `.../conclusions/{cid}` | Xóa |

### Ghi chú cá nhân (`/api/meetings/{id}/personal-notes`)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | `.../personal-notes` | Chỉ ghi chú của user đang login |
| POST | `.../personal-notes` | Tạo (`content`, `meeting_document_id`) |
| PUT | `.../personal-notes/{nid}` | Cập nhật (kiểm tra ownership) |
| DELETE | `.../personal-notes/{nid}` | Xóa (kiểm tra ownership) |

### Đăng ký phát biểu (`/api/meetings/{id}/speech-requests`)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | `.../speech-requests` | Danh sách đăng ký |
| POST | `.../speech-requests` | Nộp đăng ký (`meeting_agenda_id`, `content`) |
| PATCH | `.../speech-requests/{rid}/approve` | Duyệt |
| PATCH | `.../speech-requests/{rid}/reject` | Từ chối |
| DELETE | `.../speech-requests/{rid}` | Xóa |

### Biểu quyết (`/api/meetings/{id}/votings`)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | `.../votings` | Danh sách phiên |
| POST | `.../votings` | Tạo phiên (`title`, `type`: public/anonymous) |
| PUT | `.../votings/{vid}` | Cập nhật (chỉ khi pending) |
| DELETE | `.../votings/{vid}` | Xóa (chỉ khi pending) |
| PATCH | `.../votings/{vid}/open` | Mở bỏ phiếu |
| PATCH | `.../votings/{vid}/close` | Đóng bỏ phiếu |
| POST | `.../votings/{vid}/vote` | Bỏ phiếu (`choice`: agree/disagree/abstain) |
| GET | `.../votings/{vid}/results` | Xem kết quả (ẩn user nếu anonymous) |

---

## Response mẫu (MeetingResource)

```json
{
  "id": 1,
  "title": "Họp ban giám đốc Q1/2026",
  "description": "Họp tổng kết quý 1",
  "location": "Phòng họp A - Tầng 3",
  "start_at": "08:00:00 01/04/2026",
  "end_at": "11:00:00 01/04/2026",
  "status": "active",
  "participants_count": 10,
  "agendas_count": 5,
  "documents_count": 3,
  "conclusions_count": 2,
  "created_by": "Admin",
  "updated_by": "Admin",
  "created_at": "14:30:00 23/03/2026",
  "updated_at": "14:30:00 23/03/2026"
}
```
