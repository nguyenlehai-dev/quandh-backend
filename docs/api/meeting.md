# API Meeting

## Base path

- `/api/meetings`

## Header & phạm vi dữ liệu

- Bắt buộc: `Authorization: Bearer {token}` và `Accept: application/json`.
- Nếu màn hình đang thao tác theo tổ chức, gửi thêm `X-Organization-Id: {organization_id}`.
- Module Meeting là module riêng, không dùng chung bảng nghiệp vụ với Document.
- Không sửa `app/Modules/Core` khi thay đổi nghiệp vụ Meeting.

## Public catalog endpoints

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/meeting-types/public` | Danh sách loại cuộc họp công khai |
| GET | `/api/meeting-types/public-options` | Options loại cuộc họp |
| GET | `/api/meeting-document-types/public` | Danh sách loại tài liệu họp công khai |
| GET | `/api/meeting-document-types/public-options` | Options loại tài liệu họp |
| GET | `/api/meeting-document-fields/public` | Danh sách lĩnh vực tài liệu họp công khai |
| GET | `/api/meeting-document-fields/public-options` | Options lĩnh vực tài liệu họp |
| GET | `/api/meeting-document-signers/public` | Danh sách người ký tài liệu họp công khai |
| GET | `/api/meeting-document-signers/public-options` | Options người ký tài liệu họp |
| GET | `/api/meeting-issuing-agencies/public` | Danh sách cơ quan ban hành tài liệu họp công khai |
| GET | `/api/meeting-issuing-agencies/public-options` | Options cơ quan ban hành tài liệu họp |

## Catalog private endpoints

Áp dụng cho:

- `/api/meeting-types`
- `/api/attendee-groups`
- `/api/meeting-document-types`
- `/api/meeting-document-fields`
- `/api/meeting-document-signers`
- `/api/meeting-issuing-agencies`

| Method | Path | Mô tả |
|---|---|---|
| GET | `/{resource}/stats` | Thống kê danh mục |
| GET | `/{resource}` | Danh sách danh mục |
| POST | `/{resource}` | Tạo danh mục |
| GET | `/{resource}/{id}` | Chi tiết danh mục |
| PUT/PATCH | `/{resource}/{id}` | Cập nhật danh mục |
| DELETE | `/{resource}/{id}` | Xóa danh mục |
| POST | `/{resource}/bulk-delete` | Xóa hàng loạt |
| PATCH | `/{resource}/bulk-status` | Đổi trạng thái hàng loạt |
| PATCH | `/{resource}/{id}/status` | Đổi trạng thái |

## Meeting endpoints

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/meetings/stats` | Thống kê cuộc họp |
| GET | `/api/meetings/my-calendar` | Lịch họp cá nhân |
| GET | `/api/meetings` | Danh sách cuộc họp |
| POST | `/api/meetings` | Tạo cuộc họp |
| GET | `/api/meetings/{meeting}` | Chi tiết tổng quan cuộc họp |
| PUT/PATCH | `/api/meetings/{meeting}` | Cập nhật cuộc họp |
| DELETE | `/api/meetings/{meeting}` | Xóa cuộc họp |
| POST | `/api/meetings/bulk-delete` | Xóa hàng loạt |
| PATCH | `/api/meetings/bulk-status` | Đổi trạng thái hàng loạt |
| PATCH | `/api/meetings/{meeting}/status` | Đổi trạng thái |
| GET | `/api/meetings/export` | Xuất Excel |
| POST | `/api/meetings/import` | Nhập Excel/CSV |
| GET | `/api/meetings/{meeting}/qr-token` | Lấy QR token |
| POST | `/api/meetings/{meeting}/qr-token/regenerate` | Tạo lại QR token |
| POST | `/api/meetings/check-in` | Check-in bằng QR token |

## Detail endpoints

Áp dụng cho các child resource:

- `participants`
- `agendas`
- `documents`
- `conclusions`
- `speech-requests`
- `votings`
- `personal-notes`
- `reminders`

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/meetings/{meeting}/{child}` | Danh sách dữ liệu con |
| POST | `/api/meetings/{meeting}/{child}` | Tạo dữ liệu con |
| PUT/PATCH | `/api/meetings/{meeting}/{child}/{id}` | Cập nhật dữ liệu con |
| DELETE | `/api/meetings/{meeting}/{child}/{id}` | Xóa dữ liệu con |
| POST | `/api/meetings/{meeting}/votings/{voting}/results` | Ghi nhận kết quả biểu quyết |

## Request body chính

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

Trạng thái hợp lệ:

- `draft`
- `active`
- `in_progress`
- `completed`
- `cancelled`

Check-in QR:

```json
{
  "qr_token": "uuid-token",
  "user_id": 1
}
```

Import:

- `POST /api/meetings/import`
- `multipart/form-data`
- field `file`
- hỗ trợ `xlsx`, `xls`, `csv`

## Màn hình FE cần bám

- Menu cha: `Quản lý cuộc họp`.
- Màn danh sách: gọi `GET /api/meetings`.
- Màn chi tiết: click từ danh sách và gọi `GET /api/meetings/{meeting}` để lấy tổng quan cuộc họp.
- Các tab chi tiết dùng nested endpoints: participants, agendas, documents, conclusions, speech requests, votings, personal notes, reminders.
