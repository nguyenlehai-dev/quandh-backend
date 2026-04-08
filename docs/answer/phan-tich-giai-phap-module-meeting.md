# Phân tích giải pháp module Meeting mới

**Ngày tạo:** 2026-04-01  
**Mục đích:** Thiết kế giải pháp triển khai module `Meeting` mới theo đúng định hướng kiến trúc của hệ thống: dựa vào `Core` để phân quyền và quản lý, nhưng tách biệt dữ liệu nghiệp vụ của `Meeting` khỏi các module khác, đặc biệt không dùng chung dữ liệu với module `Document`.

---

## 1. Phạm vi bài toán

## 1.1 Đối tượng quản lý

1. **Cuộc họp**
   - Loại cuộc họp
   - Tiêu đề cuộc họp
   - Mô tả
   - Địa điểm
   - Thời gian bắt đầu
   - Thời gian kết thúc
   - Trạng thái: `draft`, `active`, `in_progress`, `completed`
   - Mã QR phục vụ check-in

2. **Thành phần tham dự cuộc họp**
   - Người tham dự
   - Vai trò tham dự: `chair`, `secretary`, `delegate`
   - Chức vụ trong cuộc họp
   - Trạng thái tham dự: `pending`, `present`, `absent`, `delegated`
   - Thời gian check-in
   - Lý do vắng mặt
   - Người được ủy quyền tham dự

3. **Chương trình nghị sự**
   - Tiêu đề nội dung họp
   - Mô tả
   - Thứ tự trình bày
   - Thời lượng
   - Người trình bày

4. **Tài liệu cuộc họp**
   - Tên tài liệu
   - Mô tả
   - Loại tài liệu cuộc họp
   - Lĩnh vực tài liệu cuộc họp
   - Cơ quan ban hành tài liệu cuộc họp
   - Người ký tài liệu cuộc họp
   - Nhiều tệp đính kèm

5. **Kết luận cuộc họp**
   - Tiêu đề kết luận
   - Nội dung kết luận
   - Gắn với cuộc họp hoặc agenda cụ thể

6. **Đăng ký phát biểu**
   - Người đăng ký
   - Nội dung đăng ký
   - Agenda liên quan
   - Trạng thái: `pending`, `approved`, `rejected`

7. **Biểu quyết**
   - Tiêu đề biểu quyết
   - Mô tả
   - Loại biểu quyết: `public`, `anonymous`
   - Trạng thái: `pending`, `open`, `closed`
   - Kết quả biểu quyết

8. **Ghi chú cá nhân**
   - Người sở hữu ghi chú
   - Nội dung ghi chú
   - Có thể gắn với tài liệu cuộc họp

## 1.2 Mục tiêu quản trị

- Quản lý tập trung toàn bộ vòng đời một cuộc họp không giấy
- Quản lý thành phần tham dự, chương trình họp, tài liệu, biểu quyết và kết luận
- Hỗ trợ check-in bằng QR
- Hỗ trợ lịch họp cá nhân
- Hỗ trợ thống kê, export, import
- Bảo đảm dữ liệu của `Meeting` độc lập với các module khác
- Bảo đảm mọi cơ chế auth, permission, organization, media, log đều dựa trên `Core`

---

## 2. Thiết kế module theo chuẩn dự án

Theo quy ước hiện tại, đề xuất giữ module riêng: `Meeting`

## 2.1 Cấu trúc thư mục đề xuất

```text
app/Modules/Meeting/
├── Enums/
│   ├── MeetingStatusEnum.php
│   ├── MeetingRoleEnum.php
│   ├── AttendanceStatusEnum.php
│   └── SpeechRequestStatusEnum.php
├── Models/
│   ├── Meeting.php
│   ├── MeetingType.php
│   ├── AttendeeGroup.php
│   ├── MeetingDocumentType.php
│   ├── MeetingDocumentField.php
│   ├── MeetingDocumentSigner.php
│   ├── MeetingIssuingAgency.php
│   ├── MeetingParticipant.php
│   ├── MeetingAgenda.php
│   ├── MeetingDocument.php
│   ├── MeetingConclusion.php
│   ├── MeetingSpeechRequest.php
│   ├── MeetingVoting.php
│   ├── MeetingVoteResult.php
│   └── MeetingPersonalNote.php
├── Requests/
├── Resources/
├── Services/
│   ├── MeetingService.php
│   ├── MeetingParticipantService.php
│   ├── MeetingAgendaService.php
│   ├── MeetingDocumentService.php
│   ├── MeetingConclusionService.php
│   ├── MeetingSpeechRequestService.php
│   ├── MeetingVotingService.php
│   ├── MeetingTypeService.php
│   ├── AttendeeGroupService.php
│   ├── MeetingDocumentTypeService.php
│   ├── MeetingDocumentFieldService.php
│   ├── MeetingDocumentSignerService.php
│   ├── MeetingIssuingAgencyService.php
│   └── MyMeetingService.php
├── Exports/
├── Imports/
├── Notifications/
├── Events/
├── Jobs/
├── Policies/
├── Routes/
│   ├── meeting.php
│   ├── my_meeting.php
│   ├── meeting_type.php
│   ├── attendee_group.php
│   ├── meeting_document_type.php
│   ├── meeting_document_field.php
│   ├── meeting_document_signer.php
│   └── meeting_issuing_agency.php
└── ...
```

## 2.2 Endpoint chuẩn cho resource chính

Áp dụng đủ bộ action chuẩn dự án:

- `stats`
- `index`
- `show`
- `store`
- `update`
- `destroy`
- `bulkDestroy`
- `bulkUpdateStatus`
- `changeStatus`
- `export`
- `import`

Tối thiểu triển khai đầy đủ cho:

- Cuộc họp (`meetings`)
- Loại cuộc họp (`meeting-types`)
- Nhóm thành phần tham dự (`attendee-groups`)
- Loại tài liệu cuộc họp (`meeting-document-types`)
- Lĩnh vực tài liệu cuộc họp (`meeting-document-fields`)
- Người ký tài liệu cuộc họp (`meeting-document-signers`)
- Cơ quan ban hành tài liệu cuộc họp (`meeting-issuing-agencies`)

---

## 3. Thiết kế dữ liệu (database)

## 3.1 Bảng loại cuộc họp

**Bảng:** `m_meeting_types`  
Mục đích: quản lý danh mục loại cuộc họp riêng cho module `Meeting`.

Trường chính đề xuất:
- `id`
- `name`
- `description` (nullable)
- `status` (`active`/`inactive`)
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## 3.2 Bảng nhóm thành phần tham dự

**Bảng:** `m_attendee_groups`  
Mục đích: quản lý nhóm người tham dự chuẩn theo loại cuộc họp.

Trường chính đề xuất:
- `id`
- `meeting_type_id` (nullable, FK -> `m_meeting_types`)
- `name`
- `description` (nullable)
- `status`
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## 3.3 Bảng thành viên nhóm tham dự

**Bảng:** `m_attendee_group_members`

Trường chính:
- `id`
- `attendee_group_id` (FK -> `m_attendee_groups`)
- `user_id` (FK -> `users`)
- `position` (nullable)
- `created_at`, `updated_at`

Ràng buộc:
- unique(`attendee_group_id`, `user_id`)

## 3.4 Bảng loại tài liệu cuộc họp

**Bảng:** `m_document_types`

Trường chính đề xuất:
- `id`
- `meeting_type_id` (nullable, FK -> `m_meeting_types`)
- `name`
- `description` (nullable)
- `status`
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## 3.5 Bảng lĩnh vực tài liệu cuộc họp

**Bảng:** `m_document_fields`

Trường chính:
- `id`
- `name`
- `description` (nullable)
- `status`
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## 3.6 Bảng người ký tài liệu cuộc họp

**Bảng:** `m_document_signers`

Trường chính:
- `id`
- `name`
- `position` (nullable)
- `description` (nullable)
- `status`
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## 3.7 Bảng cơ quan ban hành tài liệu cuộc họp

**Bảng:** `m_issuing_agencies`

Trường chính:
- `id`
- `name`
- `description` (nullable)
- `status`
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## 3.8 Bảng cuộc họp

**Bảng:** `m_meetings`

Trường chính:
- `id`
- `meeting_type_id` (FK -> `m_meeting_types`)
- `title`
- `description`
- `location`
- `start_at`
- `end_at`
- `status` (`draft`, `active`, `in_progress`, `completed`)
- `qr_token` (nullable)
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

Index khuyến nghị:
- `(organization_id, status)`
- `(organization_id, start_at)`
- `(organization_id, meeting_type_id)`

## 3.9 Bảng thành phần tham dự cuộc họp

**Bảng:** `m_participants`

Trường chính:
- `id`
- `meeting_id` (FK -> `m_meetings`)
- `user_id` (FK -> `users`)
- `position` (nullable)
- `meeting_role` (`chair`, `secretary`, `delegate`)
- `attendance_status` (`pending`, `present`, `absent`, `delegated`)
- `checkin_at` (nullable)
- `absence_reason` (nullable)
- `delegated_to_id` (nullable, FK -> `users`)
- `organization_id`
- `created_at`, `updated_at`

Ràng buộc:
- unique(`meeting_id`, `user_id`)

## 3.10 Bảng chương trình nghị sự

**Bảng:** `m_agendas`

Trường chính:
- `id`
- `meeting_id` (FK -> `m_meetings`)
- `title`
- `description` (nullable)
- `order_index`
- `duration` (nullable)
- `presenter_id` (nullable, FK -> `users`)
- `organization_id`
- `created_at`, `updated_at`

## 3.11 Bảng tài liệu cuộc họp

**Bảng:** `m_documents`

Trường chính:
- `id`
- `meeting_id` (FK -> `m_meetings`)
- `document_type_id` (FK -> `m_document_types`)
- `document_field_id` (FK -> `m_document_fields`)
- `issuing_agency_id` (FK -> `m_issuing_agencies`)
- `document_signer_id` (FK -> `m_document_signers`)
- `title`
- `description` (nullable)
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

Ghi chú triển khai:
- File đính kèm không lưu trực tiếp ở bảng này
- Upload/xóa file phải đi qua `App\Modules\Core\Services\MediaService`
- Không dùng lại `document_types`, `document_fields`, `document_signers`, `issuing_agencies` của module `Document`

## 3.12 Bảng kết luận cuộc họp

**Bảng:** `m_conclusions`

Trường chính:
- `id`
- `meeting_id`
- `meeting_agenda_id` (nullable)
- `title`
- `content`
- `organization_id`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## 3.13 Bảng đăng ký phát biểu

**Bảng:** `m_speech_requests`

Trường chính:
- `id`
- `meeting_participant_id`
- `meeting_agenda_id` (nullable)
- `content` (nullable)
- `status` (`pending`, `approved`, `rejected`)
- `organization_id`
- `created_at`, `updated_at`

## 3.14 Bảng biểu quyết

**Bảng:** `m_votings`

Trường chính:
- `id`
- `meeting_id`
- `meeting_agenda_id` (nullable)
- `title`
- `description` (nullable)
- `type` (`public`, `anonymous`)
- `status` (`pending`, `open`, `closed`)
- `organization_id`
- `created_at`, `updated_at`

## 3.15 Bảng kết quả biểu quyết

**Bảng:** `m_vote_results`

Trường chính:
- `id`
- `meeting_voting_id`
- `user_id` (nullable nếu cần hỗ trợ ẩn danh theo cách hiện thực)
- `choice`
- `organization_id`
- `created_at`, `updated_at`

Ràng buộc:
- unique(`meeting_voting_id`, `user_id`) nếu vẫn giữ rule mỗi user một phiếu

## 3.16 Bảng ghi chú cá nhân

**Bảng:** `m_personal_notes`

Trường chính:
- `id`
- `user_id`
- `meeting_id`
- `meeting_document_id` (nullable)
- `content`
- `organization_id`
- `created_at`, `updated_at`

## 3.17 Bảng nhắc việc/thông báo cuộc họp (mở rộng)

**Bảng:** `m_reminders`

Trường chính:
- `id`
- `meeting_id`
- `meeting_participant_id` (nullable)
- `remind_at`
- `sent_at` (nullable)
- `channel` (`system`, `email`, `zalo`, `sms`)
- `status` (`pending`, `sent`, `failed`)
- `error_message` (nullable)
- `organization_id`
- `created_at`, `updated_at`

---

## 4. Quy tắc nghiệp vụ trọng tâm

## 4.1 Quy tắc độc lập dữ liệu module

- `Meeting` là module nghiệp vụ độc lập
- Không dùng chung danh mục nghiệp vụ với module `Document`
- Nếu cùng ý nghĩa nghiệp vụ nhưng khác module thì phải là dữ liệu khác nhau
- Chỉ được liên kết dùng chung với dữ liệu nền tảng như `users`, `roles`, `permissions`, `media`

## 4.2 Quy tắc dựa vào Core

Tất cả giải pháp của `Meeting` phải dựa vào `Core` cho:

- auth
- permission
- organization context
- media upload
- notification
- log activity
- response format

Không triển khai cơ chế riêng ngoài `Core`.

## 4.3 Quy tắc trạng thái cuộc họp

- `draft`:
  - cho phép cập nhật thông tin chính
  - cho phép thêm/sửa/xóa participant, agenda, document, conclusion
- `active`:
  - cuộc họp đã kích hoạt
  - có thể sinh QR token nếu chưa có
  - cho phép check-in, phát biểu, biểu quyết theo quyền
- `in_progress`:
  - cuộc họp đang diễn ra
  - cho phép các thao tác vận hành như check-in, phát biểu, vote
- `completed`:
  - khóa các thao tác vận hành chính
  - chỉ cho phép xem dữ liệu và export nếu có quyền

## 4.4 Quy tắc participant

- Một user chỉ xuất hiện một lần trong cùng một cuộc họp
- Participant có thể:
  - check-in trực tiếp
  - báo vắng
  - ủy quyền nếu nghiệp vụ cho phép
- Nếu `attendance_status = absent` thì bắt buộc có `absence_reason`
- Nếu `attendance_status = delegated` thì bắt buộc có `delegated_to_id`

## 4.5 Quy tắc tài liệu cuộc họp

- Tài liệu cuộc họp chỉ được tham chiếu tới danh mục riêng của `Meeting`
- File đính kèm phải upload qua `MediaService`
- Không cho phép tạo/cập nhật tài liệu bằng foreign key trỏ sang bảng `Document` module

## 4.6 Quy tắc agenda, kết luận, speech request

- Agenda phải thuộc đúng cuộc họp
- Kết luận nếu gắn agenda thì agenda đó phải thuộc cùng cuộc họp
- Đăng ký phát biểu nếu gắn agenda thì agenda đó phải thuộc cùng cuộc họp
- Chỉ participant hợp lệ hoặc user có quyền tương ứng mới được tạo speech request

## 4.7 Quy tắc biểu quyết

- Chỉ tạo voting trong phạm vi một cuộc họp
- Chỉ được bỏ phiếu khi voting đang mở
- Mỗi user chỉ được ghi nhận một phiếu nếu giữ rule hiện tại
- Nếu `type = anonymous` thì không hiển thị danh tính người bỏ phiếu ở lớp kết quả trả về

## 4.8 Quy tắc organization scope

- Mọi dữ liệu meeting phải gắn `organization_id`
- User chỉ nhìn thấy dữ liệu thuộc organization đang chọn
- Các danh mục và nghiệp vụ con phải dùng cùng chiến lược scope
- Không để các API `globalIndex/export` làm rò rỉ dữ liệu organization khác

---

## 5. API và bộ lọc theo dõi

## 5.1 Bộ lọc index cho cuộc họp

Đề xuất hỗ trợ:

- `search`
- `status`
- `meeting_type_id`
- `start_from`, `start_to`
- `end_from`, `end_to`
- `from_date`, `to_date`
- `sort_by`: `id`, `title`, `start_at`, `end_at`, `created_at`, `updated_at`
- `sort_order`
- `limit`

## 5.2 Bộ lọc index cho tài liệu cuộc họp

- `search`
- `meeting_id`
- `meeting_type_id`
- `document_type_id`
- `document_field_id`
- `issuing_agency_id`
- `document_signer_id`
- `from_date`, `to_date`
- `sort_by`
- `sort_order`
- `limit`

## 5.3 Endpoint nghiệp vụ chính đề xuất

### Cuộc họp
- `GET /api/meetings/stats`
- `GET /api/meetings`
- `GET /api/meetings/{id}`
- `POST /api/meetings`
- `PUT /api/meetings/{id}`
- `DELETE /api/meetings/{id}`
- `POST /api/meetings/bulk-delete`
- `PATCH /api/meetings/bulk-status`
- `PATCH /api/meetings/{id}/status`
- `GET /api/meetings/export`
- `POST /api/meetings/import`

### Lịch và QR
- `GET /api/meetings/my-calendar`
- `GET /api/meetings/{id}/qr-token`
- `POST /api/meetings/{id}/qr-checkin`

### Participant
- `GET /api/meetings/{id}/participants`
- `POST /api/meetings/{id}/participants`
- `PUT /api/meetings/{id}/participants/{participantId}`
- `DELETE /api/meetings/{id}/participants/{participantId}`
- `PATCH /api/meetings/{id}/participants/{participantId}/checkin`
- `POST /api/meetings/{id}/self-checkin`
- `GET /api/meetings/{id}/available-delegates`

### Agenda
- `GET /api/meetings/{id}/agendas`
- `POST /api/meetings/{id}/agendas`
- `PUT /api/meetings/{id}/agendas/{agendaId}`
- `DELETE /api/meetings/{id}/agendas/{agendaId}`
- `PATCH /api/meetings/{id}/agendas/reorder`
- `PATCH /api/meetings/{id}/agendas/{agendaId}/set-active`

### Tài liệu cuộc họp
- `GET /api/meetings/{id}/documents`
- `POST /api/meetings/{id}/documents`
- `PUT /api/meetings/{id}/documents/{documentId}`
- `DELETE /api/meetings/{id}/documents/{documentId}`
- `GET /api/meetings/all-documents`
- `GET /api/meetings/all-documents/export`

### Kết luận
- `GET /api/meetings/{id}/conclusions`
- `POST /api/meetings/{id}/conclusions`
- `PUT /api/meetings/{id}/conclusions/{conclusionId}`
- `DELETE /api/meetings/{id}/conclusions/{conclusionId}`
- `GET /api/meetings/all-conclusions`
- `GET /api/meetings/all-conclusions/export`

### Đăng ký phát biểu
- `GET /api/meetings/{id}/speech-requests`
- `POST /api/meetings/{id}/speech-requests`
- `PATCH /api/meetings/{id}/speech-requests/{speechRequestId}/approve`
- `PATCH /api/meetings/{id}/speech-requests/{speechRequestId}/reject`
- `DELETE /api/meetings/{id}/speech-requests/{speechRequestId}`

### Biểu quyết
- `GET /api/meetings/{id}/votings`
- `POST /api/meetings/{id}/votings`
- `PUT /api/meetings/{id}/votings/{votingId}`
- `DELETE /api/meetings/{id}/votings/{votingId}`
- `PATCH /api/meetings/{id}/votings/{votingId}/open`
- `PATCH /api/meetings/{id}/votings/{votingId}/close`
- `POST /api/meetings/{id}/votings/{votingId}/vote`
- `GET /api/meetings/{id}/votings/{votingId}/results`
- `GET /api/meetings/all-votings`
- `GET /api/meetings/all-votings/export`

### Ghi chú cá nhân
- `GET /api/meetings/{id}/personal-notes`
- `POST /api/meetings/{id}/personal-notes`
- `PUT /api/meetings/{id}/personal-notes/{noteId}`
- `DELETE /api/meetings/{id}/personal-notes/{noteId}`

---

## 6. Thống kê và điều hành

## 6.1 Dashboard KPI tối thiểu

- Tổng số cuộc họp
- Số cuộc họp đang active / in_progress
- Số cuộc họp completed
- Tổng số participant
- Tổng số tài liệu
- Tổng số voting
- Tần suất cuộc họp theo tháng
- Tỷ lệ trạng thái cuộc họp

## 6.2 Báo cáo quản trị đề xuất

1. **Báo cáo theo loại cuộc họp**
   - số lượng cuộc họp theo loại
   - tỷ lệ hoàn thành theo loại

2. **Báo cáo theo participant**
   - tổng số lần tham dự
   - số lần có mặt
   - số lần vắng
   - số lần được ủy quyền hoặc ủy quyền cho người khác

3. **Báo cáo theo tài liệu cuộc họp**
   - tổng số tài liệu theo loại
   - tổng số file đính kèm

4. **Báo cáo biểu quyết**
   - số phiên biểu quyết
   - tỷ lệ mở/đóng
   - số lượng người tham gia bỏ phiếu

---

## 7. Import/Export

## 7.1 Export

- Export cuộc họp
- Export participant
- Export tài liệu cuộc họp
- Export kết luận
- Export voting
- Export các danh mục riêng của meeting

Nguyên tắc:
- export theo bộ lọc đang xem
- format thời gian thống nhất
- không kéo dữ liệu từ module khác ngoài dữ liệu nền tảng hợp lệ

## 7.2 Import

Áp dụng cho:
- cuộc họp
- loại cuộc họp
- loại tài liệu cuộc họp
- lĩnh vực tài liệu cuộc họp
- người ký
- cơ quan ban hành

Nguyên tắc:
- validate file chặt
- chỉ import theo schema của `Meeting`
- không cho import dữ liệu phụ thuộc vào bảng `Document` module

## 7.3 Upload tài liệu cuộc họp

Đề xuất API:

- `POST /api/meetings/{id}/documents`
  - hỗ trợ `files[]`
- `PUT /api/meetings/{id}/documents/{documentId}`
  - hỗ trợ thêm file mới và gỡ file cũ

Validate upload đề xuất:
- `files` => `nullable|array`
- `files.*` => `file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480`

---

## 8. Permission và logging

## 8.1 Permission cần có

### Resource chính
- `meetings.*`
- `meeting-participants.*`
- `meeting-agendas.*`
- `meeting-documents.*`
- `meeting-conclusions.*`
- `meeting-speech-requests.*`
- `meeting-votings.*`
- `meeting-personal-notes.*`

### Danh mục riêng
- `meeting-types.*`
- `attendee-groups.*`
- `meeting-document-types.*`
- `meeting-document-fields.*`
- `meeting-document-signers.*`
- `meeting-issuing-agencies.*`

## 8.2 LogActivity

Cần log đầy đủ các action quan trọng:

- tạo / cập nhật / xóa cuộc họp
- đổi trạng thái cuộc họp
- thêm / xóa participant
- check-in / self-checkin / qr-checkin
- thêm / cập nhật / xóa tài liệu
- tạo kết luận
- duyệt / từ chối phát biểu
- mở / đóng biểu quyết
- bỏ phiếu

---

## 9. Lộ trình triển khai khuyến nghị

## Giai đoạn 1: Chuẩn hóa schema và boundary
- tạo các bảng danh mục riêng của meeting
- loại bỏ phụ thuộc vào bảng `document_*`
- chuẩn hóa `organization_id` cho toàn bộ bảng meeting

## Giai đoạn 2: CRUD và dữ liệu nền
- hoàn thiện CRUD danh mục riêng
- hoàn thiện CRUD cuộc họp và các thành phần con
- chuẩn hóa request, resource, service

## Giai đoạn 3: Vận hành nâng cao
- QR check-in
- lịch họp cá nhân
- voting
- speech request
- notification

## Giai đoạn 4: Điều hành và báo cáo
- dashboard thống kê
- export/import đầy đủ
- log và kiểm soát vận hành

---

## 10. Kết luận

Giải pháp phù hợp cho `Meeting` mới là:

- giữ `Meeting` là module riêng
- dựa hoàn toàn vào `Core` để phân quyền, quản lý, media, notification, logging
- tách toàn bộ danh mục nghiệp vụ của `Meeting` thành dữ liệu riêng
- không dùng chung dữ liệu nghiệp vụ với `Document` hoặc module khác

Thiết kế này đáp ứng đúng định hướng quản lý:

- dựa vào `Core`
- tách module
- không dùng chung dữ liệu
- bảng dữ liệu có tiền tố module riêng

Đồng thời vẫn bám được pattern source hiện tại của dự án, giúp giảm rủi ro khi triển khai code lại module `Meeting`.

---

## 11. Trạng thái triển khai backend hiện tại

Backend `Meeting` đã được dựng thành module riêng tại `app/Modules/Meeting` và giữ nguyên ràng buộc bắt buộc:

- không sửa `app/Modules/Core`
- không dùng chung bảng nghiệp vụ của `Document`
- dữ liệu nghiệp vụ của Meeting dùng tiền tố bảng `m_`
- route được đăng ký từ `routes/api.php`, còn logic module nằm trong `app/Modules/Meeting`

Các lớp chính hiện có:

- `Controllers`: `MeetingController`, `MeetingChildController`, `CatalogController`
- `Models`: `Meeting`, `MeetingType`, `AttendeeGroup`, `MeetingParticipant`, `MeetingAgenda`, `MeetingDocument`, `MeetingConclusion`, `MeetingSpeechRequest`, `MeetingVoting`, `MeetingVoteResult`, `MeetingPersonalNote`, `MeetingReminder`
- `Requests`: validate cho meeting, catalog, child resource, import, check-in, bulk action, đổi trạng thái
- `Resources`: chuẩn hóa response cho list/detail/catalog/child resource
- `Services`: tách nghiệp vụ `MeetingService`, `MeetingChildService`, `CatalogService`
- `Exports` / `Imports`: xuất/nhập danh sách cuộc họp qua Excel/CSV
- `Routes`: `Routes/meeting.php`, `Routes/catalog.php`

Migration chính:

- `database/migrations/2026_04_08_210000_create_meeting_module_tables.php`

Seeder quyền riêng:

- `database/seeders/MeetingPermissionSeeder.php`

Seeder này chạy độc lập, không sửa `PermissionSeeder` hiện tại.

## 12. Bảng dữ liệu đã triển khai

Các bảng module Meeting:

- `m_meeting_types`
- `m_attendee_groups`
- `m_attendee_group_members`
- `m_document_types`
- `m_document_fields`
- `m_document_signers`
- `m_issuing_agencies`
- `m_meetings`
- `m_participants`
- `m_agendas`
- `m_documents`
- `m_conclusions`
- `m_speech_requests`
- `m_votings`
- `m_vote_results`
- `m_personal_notes`
- `m_reminders`

Ý nghĩa vận hành:

- `m_meetings` là resource cha cho màn "Danh sách Cuộc họp".
- Khi click một cuộc họp, `GET /api/meetings/{id}` trả chi tiết tổng quan để quản trị toàn bộ cuộc họp.
- Các thành phần con như người tham dự, chương trình họp, tài liệu, kết luận, phát biểu, biểu quyết, ghi chú, nhắc việc được quản lý qua nested API dưới `/api/meetings/{meeting}/...`.

## 13. API backend chính

Header bắt buộc với các API private:

- `Authorization: Bearer {token}`
- `Accept: application/json`
- `X-Organization-Id: {organization_id}` nếu màn hình đang thao tác theo tổ chức

API public cho danh mục Meeting:

- `GET /api/meeting-types/public`
- `GET /api/meeting-types/public-options`
- `GET /api/meeting-document-types/public`
- `GET /api/meeting-document-types/public-options`
- `GET /api/meeting-document-fields/public`
- `GET /api/meeting-document-fields/public-options`
- `GET /api/meeting-document-signers/public`
- `GET /api/meeting-document-signers/public-options`
- `GET /api/meeting-issuing-agencies/public`
- `GET /api/meeting-issuing-agencies/public-options`

API danh mục private:

- `/api/meeting-types`
- `/api/attendee-groups`
- `/api/meeting-document-types`
- `/api/meeting-document-fields`
- `/api/meeting-document-signers`
- `/api/meeting-issuing-agencies`

Mỗi danh mục private hỗ trợ:

- `GET /stats`
- `GET /`
- `POST /`
- `GET /{id}`
- `PUT/PATCH /{id}`
- `DELETE /{id}`
- `POST /bulk-delete`
- `PATCH /bulk-status`
- `PATCH /{id}/status`

API resource chính:

- `GET /api/meetings`
- `POST /api/meetings`
- `GET /api/meetings/{meeting}`
- `PUT/PATCH /api/meetings/{meeting}`
- `DELETE /api/meetings/{meeting}`
- `GET /api/meetings/stats`
- `GET /api/meetings/my-calendar`
- `GET /api/meetings/export`
- `POST /api/meetings/import`
- `POST /api/meetings/bulk-delete`
- `PATCH /api/meetings/bulk-status`
- `PATCH /api/meetings/{meeting}/status`
- `GET /api/meetings/{meeting}/qr-token`
- `POST /api/meetings/{meeting}/qr-token/regenerate`
- `POST /api/meetings/check-in`

API chi tiết cuộc họp:

- `/api/meetings/{meeting}/participants`
- `/api/meetings/{meeting}/agendas`
- `/api/meetings/{meeting}/documents`
- `/api/meetings/{meeting}/conclusions`
- `/api/meetings/{meeting}/speech-requests`
- `/api/meetings/{meeting}/votings`
- `/api/meetings/{meeting}/personal-notes`
- `/api/meetings/{meeting}/reminders`

Mỗi nhóm child hỗ trợ:

- `GET /api/meetings/{meeting}/{child}`
- `POST /api/meetings/{meeting}/{child}`
- `PUT/PATCH /api/meetings/{meeting}/{child}/{id}`
- `DELETE /api/meetings/{meeting}/{child}/{id}`

API biểu quyết:

- `POST /api/meetings/{meeting}/votings/{voting}/results`

## 14. Payload chính

Tạo cuộc họp:

```json
{
  "meeting_type_id": 1,
  "code": "HOP-2026-001",
  "title": "Cuộc họp giao ban",
  "description": "Nội dung tổng quan",
  "location": "Phòng họp A",
  "start_at": "2026-04-08 09:00:00",
  "end_at": "2026-04-08 11:00:00",
  "status": "draft"
}
```

Các trạng thái hợp lệ của cuộc họp:

- `draft`
- `active`
- `in_progress`
- `completed`
- `cancelled`

Import cuộc họp:

- endpoint: `POST /api/meetings/import`
- content type: `multipart/form-data`
- field: `file`
- định dạng hợp lệ: `xlsx`, `xls`, `csv`

Check-in QR:

```json
{
  "qr_token": "uuid-token",
  "user_id": 1
}
```

`user_id` có thể bỏ trống nếu check-in cho chính user đang đăng nhập.

## 15. Permission đã seed

Nhóm permission chính:

- `meetings.*`
- `meeting-types.*`
- `attendee-groups.*`
- `meeting-document-types.*`
- `meeting-document-fields.*`
- `meeting-document-signers.*`
- `meeting-issuing-agencies.*`

Các action được seed theo pattern hiện tại của backend:

- `index`
- `show`
- `store`
- `update`
- `destroy`
- `bulkDestroy`
- `changeStatus`
- `bulkUpdateStatus`
- `stats`
- `export`
- `import`

Lưu ý: quyền Meeting được seed bằng `MeetingPermissionSeeder` và gán cho role quản trị hiện có. Không sửa seeder Core.

## 16. Quy trình backend khi thay đổi module Meeting

Quy trình bắt buộc:

1. Chỉ sửa code trong `app/Modules/Meeting`, migration/seeder Meeting, route đăng ký module, docs/api Meeting nếu cần.
2. Không sửa `app/Modules/Core` nếu không có yêu cầu riêng được xác nhận.
3. Nếu thay đổi schema, tạo migration mới. Không sửa migration đã chạy trên môi trường test/prod.
4. Nếu thêm quyền, cập nhật `MeetingPermissionSeeder`, không nhét quyền Meeting vào `PermissionSeeder` chung.
5. Chạy PHP lint cho module Meeting và file route bị ảnh hưởng.
6. Chạy migration/seeder trên runtime test.
7. Smoke test API public, private, CRUD chính, nested detail, import/export, QR check-in.
8. Sau khi test pass mới commit/push theo flow `staging -> test -> promote -> prod`.

Lệnh kiểm tra local/runtime thường dùng:

```bash
php -l app/Modules/Meeting/Controllers/MeetingController.php
php -l app/Modules/Meeting/Controllers/MeetingChildController.php
php -l app/Modules/Meeting/Controllers/CatalogController.php
php artisan migrate --force
php artisan db:seed --class=MeetingPermissionSeeder --force
php artisan route:list --path=api/meetings
```

Kiểm tra không sửa Core:

```bash
git diff -- app/Modules/Core
```

Kỳ vọng output rỗng.

## 17. Checklist smoke test backend

Checklist sau mỗi lần deploy backend Meeting:

- `GET /api/meeting-types/public-options` trả `200`
- `GET /api/meetings` không token trả `401`
- login lấy token quản trị
- `GET /api/meetings` có token trả `200`
- `POST /api/meeting-types` tạo danh mục trả `201`
- `POST /api/meetings` tạo cuộc họp trả `201`
- `GET /api/meetings/{id}` trả chi tiết tổng quan cuộc họp
- `POST /api/meetings/{id}/participants` thêm người tham dự
- `POST /api/meetings/{id}/agendas` thêm chương trình họp
- `POST /api/meetings/{id}/documents` thêm tài liệu họp
- `POST /api/meetings/check-in` check-in QR trả `200`
- `POST /api/meetings/{id}/qr-token/regenerate` tạo lại QR token trả `200`
- `GET /api/meetings/export` trả file Excel
- `POST /api/meetings/import` import file Excel/CSV trả `200`
- dọn dữ liệu test sau smoke test
