# Thiết kế cấu trúc dự án

Tài liệu mô tả cấu trúc backend hiện tại của hệ thống theo hướng modular và chuẩn hóa luồng xử lý.

## 1) Tổng quan thư mục gốc

```text
quandh-backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── docs/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── artisan
├── compose.yaml
├── composer.json
├── package.json
└── phpunit.xml
```

## 2) Chuẩn cấu trúc module trong `app/Modules`

Mỗi module nên đi theo chuẩn sau:

```text
app/Modules/<Module>/
├── <Entity>Controller.php
├── Requests/
├── Services/
├── Models/
├── Resources/
├── Routes/
├── Enums/                # nếu có
├── Exports/              # nếu có
├── Imports/              # nếu có
├── Events/               # nếu có
├── Jobs/                 # nếu có
├── Notifications/        # nếu có
├── Policies/             # nếu có
├── Middleware/           # nếu có
├── Traits/               # nếu có
└── Controllers/          # chỉ dùng cho abstract/base controller dùng chung
```

Quy ước thống nhất:
- Controller nghiệp vụ cụ thể đặt ở root của module.
- `Controllers/` chỉ dùng cho lớp base hoặc helper controller dùng chung.
- Không đặt business logic trực tiếp trong `routes/api.php`.
- Route chỉ include file route theo module/resource.

## 3) Module hiện có

```text
app/Modules/
├── Auth/
│   ├── AuthController.php
│   ├── Requests/
│   ├── Routes/
│   └── Services/
├── Core/
│   ├── LogActivityController.php
│   ├── OrganizationController.php
│   ├── PermissionController.php
│   ├── RoleController.php
│   ├── SettingController.php
│   ├── UserController.php
│   ├── UserNotificationController.php
│   ├── Requests/
│   ├── Services/
│   ├── Models/
│   ├── Resources/
│   ├── Routes/
│   ├── Middleware/
│   ├── Notifications/
│   ├── Traits/
│   ├── Enums/
│   ├── Exports/
│   └── Imports/
├── Document/
│   ├── DocumentController.php
│   ├── DocumentFieldController.php
│   ├── DocumentSignerController.php
│   ├── DocumentTypeController.php
│   ├── IssuingAgencyController.php
│   ├── IssuingLevelController.php
│   ├── Requests/
│   ├── Services/
│   ├── Models/
│   ├── Resources/
│   ├── Routes/
│   ├── Enums/
│   ├── Exports/
│   ├── Imports/
│   └── Controllers/
│       └── BaseCatalogController.php
├── Meeting/
│   ├── AttendeeGroupController.php
│   ├── MeetingAgendaController.php
│   ├── MeetingConclusionController.php
│   ├── MeetingController.php
│   ├── MeetingDocumentController.php
│   ├── MeetingParticipantController.php
│   ├── MeetingPersonalNoteController.php
│   ├── MeetingSpeechRequestController.php
│   ├── MeetingTypeController.php
│   ├── MeetingVotingController.php
│   ├── MyMeetingController.php
│   ├── Requests/
│   ├── Services/
│   ├── Models/
│   ├── Resources/
│   ├── Routes/
│   ├── Events/
│   ├── Jobs/
│   ├── Notifications/
│   ├── Policies/
│   ├── Enums/
│   ├── Exports/
│   └── Imports/
└── Post/
    ├── PostController.php
    ├── PostCategoryController.php
    ├── Requests/
    ├── Services/
    ├── Models/
    ├── Resources/
    ├── Routes/
    ├── Enums/
    ├── Exports/
    └── Imports/
```

## 4) Quy ước luồng xử lý

Luồng chuẩn:

```text
Route
-> Controller
-> FormRequest validate
-> Service
-> Model / Query / Transaction
-> Resource / Collection
-> JSON response
```

Chi tiết:
- `Route`: chỉ khai báo endpoint, middleware, permission, include theo module.
- `Controller`: nhận request, gọi service, trả response chuẩn.
- `FormRequest`: validate dữ liệu đầu vào.
- `Service`: xử lý nghiệp vụ, transaction, orchestration.
- `Model`: relation, scope filter/sort, truy vấn dữ liệu.
- `Resource`: chuẩn hóa JSON trả về cho frontend.

## 5) Quy định cần giữ khi phát triển thêm

- Không viết closure chứa business logic trong `routes/api.php`.
- Không validate business payload trực tiếp trong route closure nếu có thể dùng `FormRequest`.
- Nếu thêm endpoint mới cho một module, ưu tiên tạo file trong `Routes/` của module đó.
- Nếu thêm nhóm nghiệp vụ mới cho user hiện tại, tạo controller/service riêng thay vì nhồi vào `api.php`.
- Chỉ dùng `Controllers/` cho abstract controller hoặc controller dùng chung.

## 6) Vị trí tài liệu liên quan

- Tài liệu API: `docs/api`
- Phân tích nghiệp vụ / đề xuất: `docs/answer`
- Thiết kế cơ sở dữ liệu: `docs/DATABASE_DESIGN.md`
- Tài liệu cấu trúc: `STRUCTURE_DESIGN.md`

## 7) Checklist khi thay đổi kiến trúc

Khi thêm module mới hoặc thay đổi cấu trúc lớn, cần cập nhật đồng thời:

- `STRUCTURE_DESIGN.md`
- `docs/DATABASE_DESIGN.md` nếu có migration mới
- `docs/api/*.md` hoặc tài liệu Scribe nếu thay đổi endpoint
- route include trong `routes/api.php` nếu có module/resource mới
