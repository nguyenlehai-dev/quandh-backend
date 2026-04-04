# API Cuộc họp (Meeting)

Tài liệu này phản ánh đúng contract hiện tại của module `Meeting`, gồm 4 nhóm endpoint:

- `admin meeting`: dashboard, reports, live payload, qr token, participant candidates
- `meeting core`: CRUD cuộc họp và sub-resources quản trị
- `participant meeting`: lịch họp của tôi và trung tâm tương tác cho đại biểu
- `meeting catalogs`: meeting types, attendee groups, meeting document types, meeting document fields

Tất cả endpoint private đều yêu cầu:

- `Authorization: Bearer {token}`
- `X-Organization-Id: {organization_id}`

## 1. Base paths

| Nhóm | Base path |
|---|---|
| Meeting core | `/api/meetings` |
| Admin meeting | `/api/admin/meetings` |
| Participant meeting | `/api/participant` |
| Meeting types | `/api/meeting-types` |
| Attendee groups | `/api/attendee-groups` |
| Meeting document types | `/api/meeting-document-types` |
| Meeting document fields | `/api/meeting-document-fields` |

## 2. Meeting core

### 2.1 Thống kê

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/meetings/stats` |
| **Query** | `search`, `status`, `meeting_type_id`, `from_date`, `to_date`, `sort_by`, `sort_order` |

**Response**

```json
{
  "success": true,
  "data": {
    "total": 12,
    "draft": 2,
    "active": 4,
    "in_progress": 3,
    "completed": 3
  }
}
```

### 2.2 Danh sách cuộc họp

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/meetings` |
| **Query** | `search`, `status`, `meeting_type_id`, `from_date`, `to_date`, `sort_by`, `sort_order`, `limit` |

Response là paginated collection của `MeetingResource`.

### 2.3 Chi tiết cuộc họp

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/meetings/{meeting}` |

Response trả đầy đủ:

- `meetingType`
- `participants`
- `agendas`
- `active_agenda`
- `documents`
- `conclusions`
- `votings`

### 2.4 Tạo cuộc họp

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/meetings` |
| **Body** | `meeting_type_id` (nullable), `code` (nullable), `title` (required), `description`, `location`, `start_at`, `end_at`, `status` |

### 2.5 Cập nhật cuộc họp

| | |
|---|---|
| **Method** | `PUT` / `PATCH` |
| **Path** | `/api/meetings/{meeting}` |
| **Body** | như tạo, các field đều optional |

### 2.6 Xóa cuộc họp

| | |
|---|---|
| **Method** | `DELETE` |
| **Path** | `/api/meetings/{meeting}` |

### 2.7 Bulk

| Method | Path | Body |
|---|---|---|
| `POST` | `/api/meetings/bulk-delete` | `ids[]` |
| `PATCH` | `/api/meetings/bulk-status` | `ids[]`, `status` |

### 2.8 Đổi trạng thái

| | |
|---|---|
| **Method** | `PATCH` |
| **Path** | `/api/meetings/{meeting}/status` |
| **Body** | `status` = `draft | active | in_progress | completed` |

Khi đổi sang `active`, backend tự mở khả năng check-in và sinh `qr_token` nếu chưa có.

### 2.9 Export / Import

| Method | Path | Ghi chú |
|---|---|---|
| `GET` | `/api/meetings/export` | export Excel |
| `POST` | `/api/meetings/import` | body `file` |

## 3. Sub-resources quản trị trong meeting core

### 3.1 Participants

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/participants` |
| `POST` | `/api/meetings/{meeting}/participants` |
| `PUT` | `/api/meetings/{meeting}/participants/{participant}` |
| `DELETE` | `/api/meetings/{meeting}/participants/{participant}` |
| `PATCH` | `/api/meetings/{meeting}/participants/{participant}/checkin` |

### 3.2 Reminders

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/reminders` |
| `POST` | `/api/meetings/{meeting}/reminders` |
| `PUT` | `/api/meetings/{meeting}/reminders/{reminder}` |
| `DELETE` | `/api/meetings/{meeting}/reminders/{reminder}` |

Body create/update:

- `channel`: `database | email | push`
- `remind_at`
- `status`: `pending | sent | failed | cancelled`
- `payload`: object

Nếu FE không truyền `status` khi tạo, backend tự set `pending`.

### 3.3 Agendas

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/agendas` |
| `POST` | `/api/meetings/{meeting}/agendas` |
| `PUT` | `/api/meetings/{meeting}/agendas/{agenda}` |
| `DELETE` | `/api/meetings/{meeting}/agendas/{agenda}` |
| `PATCH` | `/api/meetings/{meeting}/agendas/reorder` |
| `PATCH` | `/api/meetings/{meeting}/agendas/{agenda}/set-active` |

### 3.4 Documents

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/documents` |
| `POST` | `/api/meetings/{meeting}/documents` |
| `PUT` | `/api/meetings/{meeting}/documents/{document}` |
| `DELETE` | `/api/meetings/{meeting}/documents/{document}` |

Document hỗ trợ media collection `meeting-document-files`.

### 3.5 Conclusions

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/conclusions` |
| `POST` | `/api/meetings/{meeting}/conclusions` |
| `PUT` | `/api/meetings/{meeting}/conclusions/{conclusion}` |
| `DELETE` | `/api/meetings/{meeting}/conclusions/{conclusion}` |

### 3.6 Personal Notes

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/personal-notes` |
| `POST` | `/api/meetings/{meeting}/personal-notes` |
| `PUT` | `/api/meetings/{meeting}/personal-notes/{note}` |
| `DELETE` | `/api/meetings/{meeting}/personal-notes/{note}` |

Đây là API quản trị chung. Participant side dùng prefix `/api/participant/...`.

### 3.7 Speech Requests

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/speech-requests` |
| `POST` | `/api/meetings/{meeting}/speech-requests` |
| `PATCH` | `/api/meetings/{meeting}/speech-requests/{speechRequest}/approve` |
| `PATCH` | `/api/meetings/{meeting}/speech-requests/{speechRequest}/reject` |
| `DELETE` | `/api/meetings/{meeting}/speech-requests/{speechRequest}` |

### 3.8 Votings

| Method | Path |
|---|---|
| `GET` | `/api/meetings/{meeting}/votings` |
| `POST` | `/api/meetings/{meeting}/votings` |
| `PUT` | `/api/meetings/{meeting}/votings/{voting}` |
| `DELETE` | `/api/meetings/{meeting}/votings/{voting}` |
| `PATCH` | `/api/meetings/{meeting}/votings/{voting}/open` |
| `PATCH` | `/api/meetings/{meeting}/votings/{voting}/close` |
| `POST` | `/api/meetings/{meeting}/votings/{voting}/vote` |
| `GET` | `/api/meetings/{meeting}/votings/{voting}/results` |

Backend hiện đã khóa quan hệ `voting.meeting_id === meeting.id` để tránh gọi nhầm resource chéo cuộc họp.

## 4. Admin meeting APIs

### 4.1 Dashboard

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/admin/meetings/dashboard` |

Response gồm:

- `summary`
- `upcoming_meetings`
- `attendance_ratio`
- `status_chart`
- `monthly_chart`

### 4.2 Reports

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/admin/meetings/reports` |

Response gồm:

- `meetings_by_status`
- `meetings_by_type`
- `participant_summary`
- `monthly_frequency`

### 4.3 Danh sách/chi tiết/live

| Method | Path |
|---|---|
| `GET` | `/api/admin/meetings` |
| `GET` | `/api/admin/meetings/{meeting}` |
| `GET` | `/api/admin/meetings/{meeting}/live` |

`live` trả:

- `meeting`
- `attendance_summary`
- `active_agenda`
- `pending_speech_requests`
- `open_votings`

### 4.4 Participant candidates và QR

| Method | Path |
|---|---|
| `GET` | `/api/admin/meetings/{meeting}/participant-candidates` |
| `GET` | `/api/admin/meetings/{meeting}/qr-token` |
| `POST` | `/api/admin/meetings/{meeting}/qr-checkin` |

### 4.5 All resources cho màn quản trị tổng hợp

| Method | Path |
|---|---|
| `GET` | `/api/admin/meetings/all-documents` |
| `GET` | `/api/admin/meetings/all-conclusions` |
| `GET` | `/api/admin/meetings/all-votings` |

## 5. Participant meeting APIs

### 5.1 Lịch họp của tôi

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/participant/my-meetings` |

### 5.2 Chi tiết cuộc họp của participant

| Method | Path |
|---|---|
| `GET` | `/api/participant/meetings/{meeting}` |
| `GET` | `/api/participant/meetings/{meeting}/documents` |
| `GET` | `/api/participant/meetings/{meeting}/conclusions` |
| `POST` | `/api/participant/meetings/{meeting}/self-checkin` |
| `POST` | `/api/participant/meetings/{meeting}/qr-checkin` |

### 5.3 Personal notes của participant

| Method | Path |
|---|---|
| `GET` | `/api/participant/meetings/{meeting}/personal-notes` |
| `POST` | `/api/participant/meetings/{meeting}/personal-notes` |
| `PUT` | `/api/participant/meetings/{meeting}/personal-notes/{note}` |
| `DELETE` | `/api/participant/meetings/{meeting}/personal-notes/{note}` |

### 5.4 Speech request của participant

| Method | Path |
|---|---|
| `GET` | `/api/participant/meetings/{meeting}/speech-requests/mine` |
| `POST` | `/api/participant/meetings/{meeting}/speech-requests` |

### 5.5 Voting của participant

| Method | Path |
|---|---|
| `GET` | `/api/participant/meetings/{meeting}/votings/current` |
| `POST` | `/api/participant/meetings/{meeting}/votings/{voting}/vote` |
| `GET` | `/api/participant/meetings/{meeting}/votings/{voting}/result` |

Participant chỉ truy cập được cuộc họp mà họ được mời.

## 6. Meeting catalogs

### 6.1 Public catalogs

Các endpoint public, không cần auth:

| Method | Path |
|---|---|
| `GET` | `/api/meeting-types/public` |
| `GET` | `/api/meeting-types/public-options` |
| `GET` | `/api/meeting-document-types/public` |
| `GET` | `/api/meeting-document-types/public-options` |
| `GET` | `/api/meeting-document-fields/public` |
| `GET` | `/api/meeting-document-fields/public-options` |

### 6.2 Private catalogs

#### Meeting types

`/api/meeting-types`

- `GET /stats`
- `GET /`
- `GET /{id}`
- `POST /`
- `PUT/PATCH /{id}`
- `DELETE /{id}`
- `PATCH /{id}/status`
- `POST /bulk-delete`
- `PATCH /bulk-status`
- `GET /export`
- `POST /import`

#### Attendee groups

`/api/attendee-groups`

- full CRUD + stats + bulk + import/export
- nested members:
  - `GET /{attendeeGroup}/members`
  - `POST /{attendeeGroup}/members`
  - `PUT /{attendeeGroup}/members/{member}`
  - `DELETE /{attendeeGroup}/members/{member}`

#### Meeting document types

`/api/meeting-document-types`

- full CRUD + stats + bulk + import/export

#### Meeting document fields

`/api/meeting-document-fields`

- full CRUD + stats + bulk + import/export

## 7. Response shape chính

### MeetingResource

```json
{
  "id": 1,
  "organization_id": 2,
  "meeting_type_id": 1,
  "meeting_type_name": "Họp giao ban",
  "code": "MTG-20260404153000",
  "title": "Họp ban điều hành",
  "description": "Nội dung họp tháng 4",
  "location": "Phòng họp A",
  "start_at": "08:00:00 10/04/2026",
  "end_at": "10:00:00 10/04/2026",
  "status": "active",
  "qr_token": "abc123...",
  "checkin_opened_at": "07:30:00 10/04/2026",
  "active_agenda_id": 5,
  "active_agenda": { "id": 5, "title": "Nội dung 1" },
  "participants_count": 12,
  "agendas_count": 5,
  "documents_count": 7,
  "conclusions_count": 2,
  "created_by": "Admin",
  "updated_by": "Admin",
  "created_at": "15:30:00 04/04/2026",
  "updated_at": "15:45:00 04/04/2026"
}
```

### Meeting realtime event

Backend broadcast event:

- channel: `private-meeting.{meetingId}`
- event name: `meeting.realtime.updated`

Payload hiện dùng dạng:

```json
{
  "meeting_id": 12,
  "event_type": "voting.opened",
  "payload": {
    "voting_id": 3,
    "title": "Thông qua nghị quyết"
  }
}
```

`event_type` đang được phát cho các tình huống:

- `meeting.status-changed`
- `agenda.set-active`
- `participant.self-checkin`
- `speech-request.created`
- `speech-request.status-changed`
- `voting.opened`
- `voting.closed`
- `voting.result-updated`
