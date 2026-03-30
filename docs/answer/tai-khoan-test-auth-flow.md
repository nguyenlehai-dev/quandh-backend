# Tai Khoan Test Auth Flow

Seeder lien quan:
- `Database\\Seeders\\OrganizationDemoSeeder`
- `Database\\Seeders\\AuthFlowDemoSeeder`

Lenh seed:

```bash
php artisan db:seed --class=AuthFlowDemoSeeder --force
```

## Muc tieu

Bo tai khoan nay dung de test dung 3 luong auth ma frontend dang ap dung:

1. Dang nhap xong vao thang app neu da co to chuc hien tai
2. Dang nhap xong vao trang chon to chuc neu chua co to chuc hien tai
3. Dang nhap vao app roi dung chuc nang chuyen to chuc de quay lai trang chon to chuc

## Tai khoan test

| Case | User name | Email | Password | Mong doi |
|------|-----------|-------|----------|----------|
| Vao thang app | `flow_direct` | `flow.direct@example.com` | `quandcore**11` | Dang nhap xong vao thang trang quan ly |
| Chon to chuc | `flow_select` | `flow.select@example.com` | `quandcore**11` | Dang nhap xong vao `/select-organization` |
| Chuyen to chuc | `flow_switch` | `flow.switch@example.com` | `quandcore**11` | Dang nhap vao app, sau do bam chuyen to chuc de quay lai trang chon |

## Cau hinh tung case

### 1. flow_direct

- Duoc gan 1 to chuc duy nhat
- Backend tu xac dinh `current_organization_id`
- Frontend bo qua man chon to chuc

### 2. flow_select

- Duoc gan nhieu to chuc
- `user_preferences.current_organization_id = null`
- Backend tra `current_organization_id = null`
- Frontend phai vao man chon to chuc

### 3. flow_switch

- Duoc gan nhieu to chuc
- Co `current_organization_id` hop le
- Frontend vao thang app
- Sau do nguoi dung dung nut `Chuyen to chuc` de quay lai man chon

## Ghi chu backend

Logic xac dinh to chuc hien tai nam trong:
- `app/Modules/Auth/Services/AuthService.php`

Quy tac:
- Neu user khong co to chuc nao -> `current_organization_id = null`
- Neu user chi co 1 to chuc -> backend tu gan org do
- Neu user co nhieu to chuc va da co preference hop le -> tra preference do
- Neu user co nhieu to chuc nhung chua co preference -> tra `null`
