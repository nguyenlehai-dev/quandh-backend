# Phân tích module Meeting hiện tại

Tài liệu này phản ánh trạng thái backend `Meeting` sau khi đã mở rộng theo hướng `paperless meeting`, tách rõ `Admin` và `Participant`, nhưng vẫn dùng chung nền `Core` cho `users`, `organizations`, `permissions`, `roles`, `media`, `log activity`.

## 1. Boundary hiện tại

Module `Meeting` là module nghiệp vụ độc lập. Dữ liệu chính của module có prefix `m_`.

Các bảng chính đang dùng:

- `m_meetings`
- `m_meeting_types`
- `m_attendee_groups`
- `m_attendee_group_members`
- `m_participants`
- `m_agendas`
- `m_documents`
- `m_document_types`
- `m_document_fields`
- `m_personal_notes`
- `m_speech_requests`
- `m_votings`
- `m_vote_results`
- `m_conclusions`
- `m_checkins`
- `m_reminders`

Module không dùng lại danh mục nghiệp vụ của module khác. Chỉ liên kết nền tảng với:

- `users`
- `organizations`
- `media`
- `permissions`
- `roles`

## 2. Kiến trúc API hiện tại

### Nhóm quản trị cuộc họp

- `/api/meetings`
- `/api/admin/meetings`
- `/api/meeting-types`
- `/api/attendee-groups`
- `/api/meeting-document-types`
- `/api/meeting-document-fields`

### Nhóm đại biểu

- `/api/participant/my-meetings`
- `/api/participant/meetings/{meeting}/...`

Thiết kế này bám đúng hướng:

- admin có dashboard, reports, live controller data
- participant có lịch họp của tôi và trung tâm tương tác riêng

## 3. Luồng nghiệp vụ đang được backend hỗ trợ

### Giai đoạn chuẩn bị

1. Tạo meeting
2. Gán participant
3. Soạn agenda
4. Upload documents
5. Soạn voting, conclusions, reminders
6. Chuyển trạng thái meeting sang `active`

### Giai đoạn check-in và vận hành

1. Backend có `qr_token`
2. Participant có thể:
   - `self-checkin`
   - `qr-checkin`
3. Admin có thể:
   - lấy `live` payload
   - set `active agenda`
   - xem `pending speech requests`
   - mở/đóng voting

### Giai đoạn tương tác đại biểu

Participant có thể:

- xem `my-meetings`
- xem chi tiết meeting được mời
- xem documents
- CRUD personal notes
- gửi speech request
- xem voting đang mở
- bỏ phiếu
- xem conclusions

## 4. Các điểm backend đã xử lý đúng nghiệp vụ

### 4.1 Cô lập participant access

Participant chỉ xem được meeting nếu có trong `m_participants`.

Backend đang enforce tại `MeetingService::ensureParticipantAccess()`.

### 4.2 Cô lập ghi chú cá nhân

`MeetingPersonalNoteService` luôn giới hạn theo `auth()->id()`.

Ngoài ra controller participant đã được siết thêm để note phải thuộc đúng `meeting` đang gọi.

### 4.3 Chống gọi chéo resource giữa các meeting

Backend hiện đã kiểm tra quan hệ cha-con cho các resource nhạy cảm:

- `agenda`
- `speechRequest`
- `voting`
- `reminder`
- `participant personal note`

Điều này ngăn việc dùng `id` của resource thuộc meeting A nhưng gọi qua URL của meeting B.

### 4.4 Voting public và anonymous

`m_vote_results` vẫn lưu `user_id` để chống vote trùng.

Khi `MeetingVoting.type = anonymous`:

- backend chỉ trả `summary`
- `details` bị ẩn

Khi `type = public`:

- backend trả thêm từng phiếu trong `details`

### 4.5 Reminder mặc định

Khi tạo reminder mà client không truyền `status`, backend tự đặt:

- `pending`

Điểm này đã được khóa bằng test.

### 4.6 Dashboard và reports

Backend đã có:

- `dashboard()`
- `reports()`
- `monthlyFrequency()`

`monthlyFrequency()` hiện đã được làm tương thích đa DB, không khóa cứng theo MySQL.

## 5. Realtime hiện tại

Backend đã phát event:

- channel: `private-meeting.{meetingId}`
- event: `meeting.realtime.updated`

Các `event_type` đang phát:

- `meeting.status-changed`
- `agenda.set-active`
- `participant.self-checkin`
- `speech-request.created`
- `speech-request.status-changed`
- `voting.opened`
- `voting.closed`
- `voting.result-updated`

Điều này đủ để FE `Admin controller` và `Participant view` sync state qua Echo/Reverb.

## 6. Catalog riêng của module

Module `Meeting` hiện đã có catalog riêng, không dùng chung module khác:

- `meeting-types`
- `attendee-groups`
- `attendee-group-members`
- `meeting-document-types`
- `meeting-document-fields`

Mỗi catalog đang hỗ trợ:

- CRUD
- `stats`
- `bulk-delete`
- `bulk-status`
- `export`
- `import`

Riêng `attendee-groups` có nested members riêng.

## 7. Admin APIs nổi bật

Các API quan trọng đã có:

- `GET /api/admin/meetings/dashboard`
- `GET /api/admin/meetings/reports`
- `GET /api/admin/meetings/{meeting}/live`
- `GET /api/admin/meetings/{meeting}/participant-candidates`
- `GET /api/admin/meetings/{meeting}/qr-token`
- `POST /api/admin/meetings/{meeting}/qr-checkin`
- `GET /api/admin/meetings/all-documents`
- `GET /api/admin/meetings/all-conclusions`
- `GET /api/admin/meetings/all-votings`

## 8. Participant APIs nổi bật

- `GET /api/participant/my-meetings`
- `GET /api/participant/meetings/{meeting}`
- `GET /api/participant/meetings/{meeting}/documents`
- `GET/POST/PUT/DELETE /api/participant/meetings/{meeting}/personal-notes`
- `GET /api/participant/meetings/{meeting}/conclusions`
- `GET /api/participant/meetings/{meeting}/speech-requests/mine`
- `POST /api/participant/meetings/{meeting}/speech-requests`
- `GET /api/participant/meetings/{meeting}/votings/current`
- `POST /api/participant/meetings/{meeting}/votings/{voting}/vote`
- `GET /api/participant/meetings/{meeting}/votings/{voting}/result`
- `POST /api/participant/meetings/{meeting}/self-checkin`
- `POST /api/participant/meetings/{meeting}/qr-checkin`

## 9. Mức độ ổn định hiện tại

Module đã có feature tests cho các luồng chính:

- tạo và xem `meeting types`
- participant access control
- self check-in
- set active agenda + live endpoint
- participant personal notes
- participant speech requests
- participant voting
- admin reminders
- admin approve/reject speech requests
- admin open/close voting
- admin attendee groups + members
- admin dashboard/reports/candidates/qr token

Hiện tại suite đang pass:

- `12 tests`
- `130 assertions`

## 10. Kết luận

Backend `Meeting` hiện không còn là CRUD meeting đơn giản nữa mà đã có:

- meeting core
- participant center
- admin live controller payload
- catalog riêng của module
- realtime event nền
- test cho các flow chính

Điểm còn có thể làm thêm sau này là:

- test sâu hơn cho `all-documents`, `all-conclusions`, `all-votings`
- test `import/export`
- FE consume realtime đầy đủ hơn ở mọi màn
