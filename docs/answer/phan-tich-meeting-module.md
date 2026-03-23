# Phân tích Module Cuộc họp không giấy (Paperless Meeting)

**Ngày tạo:** 2026-03-23
**Mục đích:** Phân tích nghiệp vụ, kiến trúc dữ liệu, và giải pháp kỹ thuật cho Module Meeting.

---

## 1. Tổng quan kiến trúc

Module Meeting được xây dựng trên kiến trúc **Decoupled (API-first)** với 9 bảng dữ liệu:
- `m_meetings` — Bảng chính
- `m_participants` — Pivot mở rộng (users ↔ meetings)
- `m_agendas` — Chương trình nghị sự
- `m_documents` — Tài liệu (file via Spatie MediaLibrary)
- `m_personal_notes` — Ghi chú cá nhân (cô lập dữ liệu)
- `m_speech_requests` — Đăng ký phát biểu
- `m_votings` — Phiên biểu quyết
- `m_vote_results` — Kết quả bỏ phiếu
- `m_conclusions` — Kết luận (1:N với meeting)

---

## 2. Luồng nghiệp vụ

### Bước 1: Thiết lập & Kích hoạt
1. Quản lý tạo cuộc họp (`POST /api/meetings`, status=draft)
2. Gán đại biểu (`POST /api/meetings/{id}/participants`)
3. Soạn chương trình (`POST /api/meetings/{id}/agendas`)
4. Đính kèm tài liệu (`POST /api/meetings/{id}/documents`)
5. Kích hoạt cuộc họp (`PATCH /api/meetings/{id}/status`, status=active)
   → **Trigger:** Gửi thông báo (Firebase, Email, SMS) — giai đoạn sau

### Bước 2: Trước cuộc họp
- Đại biểu xem tài liệu (`GET /api/meetings/{id}/documents`)
- Ghi chú cá nhân trên tài liệu (`POST /api/meetings/{id}/personal-notes`)
- Đăng ký phát biểu (`POST /api/meetings/{id}/speech-requests`)
- Quản lý duyệt/từ chối (`PATCH .../speech-requests/{id}/approve`)

### Bước 3: Trong cuộc họp
- Đổi trạng thái → "Đang họp" (`PATCH /api/meetings/{id}/status`, status=in_progress)
- Điểm danh (`PATCH .../participants/{id}/checkin`, attendance_status=present)
- Quản lý mở phiên bỏ phiếu (`PATCH .../votings/{id}/open`)
- Đại biểu bỏ phiếu (`POST .../votings/{id}/vote`, choice=agree/disagree/abstain)
- Quản lý đóng phiên (`PATCH .../votings/{id}/close`)
- Xem kết quả (`GET .../votings/{id}/results`)

### Bước 4: Kết luận & Lưu trữ
- Thư ký tạo kết luận (`POST /api/meetings/{id}/conclusions`)
- Đổi trạng thái → "Kết thúc" (`PATCH /api/meetings/{id}/status`, status=completed)
- Đại biểu truy cập lại ghi chú cá nhân + tài liệu

---

## 3. Giải pháp biểu quyết ẩn danh

**Vấn đề:** Đảm bảo tính minh bạch (mỗi người chỉ bỏ 1 phiếu) nhưng không lộ danh tính.

**Giải pháp:**
- Bảng `m_vote_results` vẫn lưu `user_id` để **chống bỏ phiếu trùng** (UNIQUE constraint trên `meeting_voting_id` + `user_id`)
- Khi `m_votings.type = 'anonymous'`, API response **không trả về** `user_id` và `user_name` trong `details[]`
- Chỉ trả về `summary` (tổng hợp: agree, disagree, abstain)

Cách triển khai trong `MeetingVotingService::results()`:
```php
if (! $voting->isAnonymous()) {
    $details = $results->load('user')->map(fn ($r) => [...]);
} else {
    $details = []; // Ẩn thông tin chi tiết
}
```

---

## 4. Cơ chế cô lập ghi chú cá nhân

**Vấn đề:** Đại biểu A không được xem ghi chú của đại biểu B.

**Giải pháp 3 lớp:**

1. **Model scope:** `scopeOwnedByAuth()` — tự động filter `where('user_id', auth()->id())`
2. **Service:** `MeetingPersonalNoteService::index()` luôn gọi `->ownedByAuth()`
3. **Controller:** `update()` và `destroy()` kiểm tra `$note->user_id !== auth()->id()` → trả 403

---

## 5. Pivot Table mở rộng (m_participants)

Bảng `m_participants` không chỉ là bảng trung gian đơn thuần mà là **Pivot mở rộng** chứa dữ liệu nghiệp vụ:

| Cột | Ý nghĩa |
|-----|---------|
| `position` | Chức vụ riêng cho cuộc họp này (vd: Giám đốc, Trưởng phòng) |
| `meeting_role` | Vai trò chức năng (chair/secretary/delegate) |
| `attendance_status` | Trạng thái điểm danh (pending/present/absent) |
| `checkin_at` | Thời điểm điểm danh chính xác |
| `absence_reason` | Lý do vắng mặt |

Sử dụng **Pivot Model** (`MeetingParticipant`) thay vì pivot thông thường để có thể:
- Thêm relationships (speechRequests)
- Áp dụng Eloquent scopes
- Validate dữ liệu

---

## 6. Phân quyền

Module Meeting bổ sung **8 nhóm resource** vào PermissionSeeder với tổng cộng ~50 permissions:
- `meetings.*` (11 actions)
- `meeting-participants.*` (5 actions)
- `meeting-agendas.*` (5 actions)
- `meeting-documents.*` (4 actions)
- `meeting-conclusions.*` (4 actions)
- `meeting-personal-notes.*` (4 actions)
- `meeting-speech-requests.*` (5 actions)
- `meeting-votings.*` (8 actions)
