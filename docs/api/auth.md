# API Xác thực (Auth)

Tài liệu này phản ánh đúng contract hiện tại của module `Auth` trong backend, bao gồm response theo chuẩn JSON của hệ thống, cơ chế `Sanctum`, và flow chọn tổ chức làm việc bằng `X-Organization-Id`.

**Base path:** `/api/auth`

## Tổng quan

- `POST /api/auth/login`: đăng nhập, trả token + user + danh sách tổ chức + roles/permissions/abilities theo tổ chức hiện tại.
- `GET /api/user`: lấy lại `me` để FE khởi tạo CASL khi refresh.
- `POST /api/auth/logout`: hủy token hiện tại.
- `POST /api/auth/switch-organization`: chuyển tổ chức làm việc và trả lại bộ quyền mới.
- `POST /api/auth/forgot-password`: gửi mail reset.
- `POST /api/auth/reset-password`: đặt lại mật khẩu.

## Header rules

| Endpoint | Authorization | X-Organization-Id |
|---|---|---|
| `POST /api/auth/login` | Không | Không |
| `POST /api/auth/forgot-password` | Không | Không |
| `POST /api/auth/reset-password` | Không | Không |
| `POST /api/auth/logout` | Có | Không |
| `POST /api/auth/switch-organization` | Có | Không |
| `GET /api/user` | Có | Có |
| Các API nghiệp vụ khác | Có | Có |

## 1. Đăng nhập

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/auth/login` |
| **Body** | `email` (required, nhận cả email hoặc `user_name`), `password` (required) |

**Response 200**

```json
{
  "success": true,
  "message": "Đăng nhập thành công.",
  "data": {
    "access_token": "1|xxx...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Admin"
    },
    "available_organizations": [
      {
        "id": 2,
        "name": "Sở Nội vụ"
      }
    ],
    "current_organization_id": 2,
    "roles": ["admin"],
    "permissions": ["users.index", "meetings.index"],
    "abilities": [
      { "action": "index", "subject": "User" },
      { "action": "index", "subject": "Meeting" }
    ]
  }
}
```

**Quy tắc hiện tại**

- `current_organization_id` ưu tiên lấy từ `user_preferences.current_organization_id` nếu còn hợp lệ.
- Nếu user chỉ có đúng 1 tổ chức truy cập được, backend tự gán `current_organization_id`.
- Nếu user có nhiều tổ chức nhưng chưa có preference hợp lệ, backend trả `current_organization_id = null`.
- `roles`, `permissions`, `abilities` được tính theo `current_organization_id`.

**Response lỗi**

- `401`: `Thông tin đăng nhập không chính xác`
- `403`: `Tài khoản của bạn đã bị khóa`

## 2. Me

| | |
|---|---|
| **Method** | `GET` |
| **Path** | `/api/user` |
| **Header** | `Authorization: Bearer {token}`, `X-Organization-Id: {organization_id}` |

**Response 200**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin"
    },
    "roles": ["admin"],
    "permissions": ["users.index", "meetings.index"],
    "abilities": [
      { "action": "index", "subject": "User" },
      { "action": "index", "subject": "Meeting" }
    ]
  }
}
```

`GET /api/user` dùng để FE khởi tạo lại quyền theo tổ chức đang chọn khi reload trang.

## 3. Đăng xuất

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/auth/logout` |
| **Header** | `Authorization: Bearer {token}` |

**Response 200**

```json
{
  "success": true,
  "message": "Đã đăng xuất"
}
```

Endpoint này chỉ xóa token hiện tại.

## 4. Chuyển tổ chức làm việc

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/auth/switch-organization` |
| **Header** | `Authorization: Bearer {token}` |
| **Body** | `organization_id` (required, integer) |

**Response 200**

```json
{
  "success": true,
  "message": "Đã chuyển tổ chức làm việc.",
  "data": {
    "current_organization_id": 3,
    "current_organization": {
      "id": 3,
      "name": "UBND Quận 1"
    },
    "roles": ["editor"],
    "permissions": ["posts.index", "meetings.index"],
    "abilities": [
      { "action": "index", "subject": "Post" },
      { "action": "index", "subject": "Meeting" }
    ]
  }
}
```

**Response lỗi**

- `403`: `Tổ chức không hợp lệ hoặc đã ngừng hoạt động.`
- `403`: `Bạn không có quyền truy cập tổ chức đã chọn.`

## 5. Quên mật khẩu

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/auth/forgot-password` |
| **Body** | `email` (required, email) |

**Response 200**

```json
{
  "success": true,
  "message": "Link reset đã được gửi vào Email"
}
```

**Response 400**

```json
{
  "success": false,
  "message": "Không thể gửi mail"
}
```

## 6. Đặt lại mật khẩu

| | |
|---|---|
| **Method** | `POST` |
| **Path** | `/api/auth/reset-password` |
| **Body** | `email`, `password`, `password_confirmation`, `token` |

**Response 200**

```json
{
  "success": true,
  "message": "Mật khẩu đã được đặt lại"
}
```

**Response 400**

```json
{
  "success": false,
  "message": "Không thể đặt lại mật khẩu"
}
```

## Ghi chú cho FE

- FE phải lưu tối thiểu: `access_token`, `current_organization_id`, `available_organizations`, `abilities`.
- Với mọi API sau login, FE cần gắn `Authorization` và `X-Organization-Id`, trừ `logout` và `switch-organization`.
- Sau khi gọi `switch-organization`, FE cần thay toàn bộ `roles`, `permissions`, `abilities` hiện tại bằng bộ mới backend trả về.
