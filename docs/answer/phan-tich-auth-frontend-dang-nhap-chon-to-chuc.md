# Phân tích Auth từ Frontend: Đăng nhập và Chọn Tổ chức

Tài liệu này được cập nhật theo contract hiện tại của backend `Auth`.

## 1. Bản chất flow hiện tại

Backend không giữ “tổ chức hiện tại” trong session theo kiểu stateful. FE phải tự giữ:

- `access_token`
- `available_organizations`
- `current_organization_id`
- `roles`
- `permissions`
- `abilities`

Backend chỉ hỗ trợ thêm một lớp tiện ích:

- nếu bảng `user_preferences` tồn tại và có cột phù hợp, backend sẽ nhớ `current_organization_id`
- nếu user chỉ có đúng 1 tổ chức, backend tự chọn luôn tổ chức đó sau login

## 2. Login

**Endpoint:** `POST /api/auth/login`

**Body**

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

`email` hiện nhận cả email thật hoặc `user_name`.

**Response thành công**

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
      },
      {
        "id": 3,
        "name": "UBND Quận 1"
      }
    ],
    "current_organization_id": null,
    "roles": [],
    "permissions": [],
    "abilities": []
  }
}
```

## 3. Giải thích `current_organization_id`

Có 3 nhánh thực tế:

### Trường hợp A: không có organization

- `available_organizations = []`
- `current_organization_id = null`
- FE nên chặn vào app nghiệp vụ

### Trường hợp B: có đúng 1 organization

- backend tự set `current_organization_id`
- đồng thời trả luôn `roles`, `permissions`, `abilities` theo org đó
- FE có thể vào app ngay

### Trường hợp C: có nhiều organization

- nếu backend tìm thấy preference hợp lệ trong `user_preferences`, nó trả luôn org đó
- nếu không có preference hợp lệ, `current_organization_id = null`
- FE phải mở màn chọn tổ chức

## 4. Switch organization

**Endpoint:** `POST /api/auth/switch-organization`

**Header**

```http
Authorization: Bearer {token}
```

**Body**

```json
{
  "organization_id": 3
}
```

**Response**

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

Ý nghĩa:

- validate user có quyền vào org đó hay không
- trả bộ quyền mới đúng theo org mới
- nếu hệ thống có `user_preferences`, backend sẽ lưu org này để login lần sau nhớ lại

## 5. `GET /api/user`

Đây là endpoint FE nên dùng khi refresh trang để rebuild CASL ability.

**Header bắt buộc**

```http
Authorization: Bearer {token}
X-Organization-Id: {organization_id}
```

**Response**

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

## 6. Quy tắc header cho FE

### Không cần `X-Organization-Id`

- `POST /api/auth/login`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/logout`
- `POST /api/auth/switch-organization`
- tất cả route public

### Bắt buộc có `X-Organization-Id`

- `GET /api/user`
- toàn bộ API nghiệp vụ private như `users`, `roles`, `settings`, `meetings`, `documents`, `posts`

## 7. Gợi ý flow FE chuẩn

1. Gọi `POST /api/auth/login`
2. Lưu `access_token`
3. Nếu `current_organization_id` có giá trị:
   FE lưu luôn org hiện tại và dùng `abilities` backend trả về
4. Nếu `current_organization_id = null` nhưng có nhiều organization:
   FE mở màn chọn tổ chức
5. Khi user chọn org:
   gọi `POST /api/auth/switch-organization`
6. Từ đây, mọi request nghiệp vụ phải gắn:
   `Authorization` + `X-Organization-Id`
7. Khi refresh trình duyệt:
   gọi `GET /api/user` để lấy lại `roles`, `permissions`, `abilities`

## 8. Kết luận

Backend auth hiện tại đã phù hợp với FE đa tổ chức:

- login trả sẵn danh sách org và quyền
- switch-organization trả lại bộ quyền mới
- `/api/user` dùng để rebuild state
- FE là nơi giữ state tổ chức hiện tại và gắn `X-Organization-Id` cho request nghiệp vụ
