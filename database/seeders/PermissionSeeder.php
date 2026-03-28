<?php

namespace Database\Seeders;

use App\Modules\Core\Enums\StatusEnum;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed permission, role, organization và phân quyền cho dự án.
 *
 * Khi thêm module mới hoặc thêm action (stats, index, show, store, ...) vào module,
 * bắt buộc cập nhật danh sách PERMISSIONS bên dưới cho đầy đủ, sau đó chạy lại seed.
 */
class PermissionSeeder extends Seeder
{
    /** Guard thống nhất cho Spatie (web + API Sanctum), tránh nhân đôi permission trong DB. */
    protected const GUARD = 'api';

    /**
     * Danh sách đầy đủ permission theo module và resource.
     * Định dạng: 'resource.action' — resource trùng prefix API (users, permissions, roles, organizations, posts, post-categories).
     * Khi thêm module/chức năng: bổ sung vào đúng nhóm và chạy sail artisan db:seed --class=PermissionSeeder.
     */
    protected static array $PERMISSIONS = [
        // Core - Users
        'users' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Core - Permissions (có description, sort_order, parent_id để nhóm frontend)
        'permissions' => [
            'stats', 'index', 'tree', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'export', 'import',
        ],
        // Core - Roles (bảng roles chuẩn Spatie, không có cột status)
        'roles' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'export', 'import',
        ],
        // Core - Organizations (cấu trúc cây parent_id)
        'organizations' => [
            'stats', 'index', 'tree', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Post - Bài viết
        'posts' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
            'incrementView',
        ],
        // Post - Danh mục bài viết
        'post-categories' => [
            'stats', 'index', 'tree', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Core - Nhật ký truy cập
        'log-activities' => [
            'stats', 'index', 'show', 'export', 'destroy', 'bulkDestroy',
            'destroyByDate', 'destroyAll',
        ],
        // Document - Văn bản
        'documents' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Document - Loại văn bản
        'document-types' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Document - Cơ quan ban hành
        'issuing-agencies' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Document - Cấp ban hành
        'issuing-levels' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Document - Người ký
        'document-signers' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Document - Lĩnh vực
        'document-fields' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Core - Cấu hình hệ thống
        'settings' => [
            'index', 'show', 'update',
        ],
        // Meeting - Cuộc họp
        'meetings' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        // Meeting - Thành viên cuộc họp
        'meeting-participants' => [
            'index', 'store', 'update', 'destroy', 'checkin',
        ],
        // Meeting - Chương trình nghị sự
        'meeting-agendas' => [
            'index', 'store', 'update', 'destroy', 'reorder',
        ],
        // Meeting - Tài liệu cuộc họp
        'meeting-documents' => [
            'index', 'store', 'update', 'destroy',
        ],
        // Meeting - Kết luận cuộc họp
        'meeting-conclusions' => [
            'index', 'store', 'update', 'destroy',
        ],
        // Meeting - Ghi chú cá nhân
        'meeting-personal-notes' => [
            'index', 'store', 'update', 'destroy',
        ],
        // Meeting - Đăng ký phát biểu
        'meeting-speech-requests' => [
            'index', 'store', 'approve', 'reject', 'destroy',
        ],
        // Meeting - Biểu quyết
        'meeting-votings' => [
            'index', 'store', 'update', 'destroy', 'open', 'close', 'vote', 'results',
        ],
    ];

    public function run(): void
    {
        $this->migrateGuardWebToApi();
        $this->seedOrganizations();
        $this->seedPermissions();
        $this->seedRoles();
        $this->assignPermissionsToRoles();
        $this->seedFixedUsersAndAssignRoles();
    }

    /** Chuyển permission/role từ guard web sang api (một lần khi đổi chiến lược guard). */
    protected function migrateGuardWebToApi(): void
    {
        Permission::where('guard_name', 'web')->update(['guard_name' => 'api']);
        Role::where('guard_name', 'web')->update(['guard_name' => 'api']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Tạo organization mặc định. */
    protected function seedOrganizations(): void
    {
        Organization::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default',
                'description' => 'Organization mặc định của hệ thống',
                'status' => StatusEnum::Active->value,
            ]
        );
    }

    /** Nhãn nhóm permission theo resource (để description). */
    protected static array $RESOURCE_LABELS = [
        'users' => 'Người dùng',
        'permissions' => 'Quyền',
        'roles' => 'Vai trò',
        'organizations' => 'Tổ chức',
        'posts' => 'Bài viết',
        'post-categories' => 'Danh mục bài viết',
        'log-activities' => 'Nhật ký truy cập',
        'documents' => 'Văn bản',
        'document-types' => 'Loại văn bản',
        'issuing-agencies' => 'Cơ quan ban hành',
        'issuing-levels' => 'Cấp ban hành',
        'document-signers' => 'Người ký',
        'document-fields' => 'Lĩnh vực',
        'settings' => 'Cấu hình hệ thống',
        'meetings' => 'Cuộc họp',
        'meeting-participants' => 'Thành viên cuộc họp',
        'meeting-agendas' => 'Chương trình nghị sự',
        'meeting-documents' => 'Tài liệu cuộc họp',
        'meeting-conclusions' => 'Kết luận cuộc họp',
        'meeting-personal-notes' => 'Ghi chú cá nhân',
        'meeting-speech-requests' => 'Đăng ký phát biểu',
        'meeting-votings' => 'Biểu quyết',
    ];

    /** Nhãn action (để description). */
    protected static array $ACTION_LABELS = [
        'stats' => 'Thống kê',
        'index' => 'Danh sách',
        'tree' => 'Cây',
        'show' => 'Chi tiết',
        'store' => 'Tạo mới',
        'update' => 'Cập nhật',
        'destroy' => 'Xóa',
        'bulkDestroy' => 'Xóa hàng loạt',
        'bulkUpdateStatus' => 'Cập nhật trạng thái hàng loạt',
        'changeStatus' => 'Đổi trạng thái',
        'export' => 'Xuất Excel',
        'import' => 'Nhập Excel',
        'incrementView' => 'Tăng lượt xem',
        'destroyByDate' => 'Xóa theo khoảng thời gian',
        'destroyAll' => 'Xóa toàn bộ',
        'checkin' => 'Điểm danh',
        'reorder' => 'Sắp xếp lại',
        'approve' => 'Duyệt',
        'reject' => 'Từ chối',
        'open' => 'Mở biểu quyết',
        'close' => 'Đóng biểu quyết',
        'vote' => 'Bỏ phiếu',
        'results' => 'Xem kết quả',
    ];

    /** Tạo đầy đủ permission từ danh sách PERMISSIONS (kèm description, sort_order, parent_id). */
    protected function seedPermissions(): void
    {
        $sortOrder = 0;
        $parentIds = [];

        foreach (self::$PERMISSIONS as $resource => $actions) {
            $groupName = "group:{$resource}";
            $groupLabel = self::$RESOURCE_LABELS[$resource] ?? ucfirst($resource);
            $group = Permission::firstOrCreate(
                ['name' => $groupName, 'guard_name' => self::GUARD],
                ['name' => $groupName, 'guard_name' => self::GUARD, 'description' => $groupLabel, 'sort_order' => $sortOrder++, 'parent_id' => null]
            );
            $parentIds[$resource] = $group->id;

            foreach ($actions as $idx => $action) {
                $name = "{$resource}.{$action}";
                $actionLabel = self::$ACTION_LABELS[$action] ?? $action;
                $desc = ($groupLabel ?? '').' - '.$actionLabel;
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => self::GUARD],
                    ['description' => $desc, 'sort_order' => $idx, 'parent_id' => $group->id]
                );
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Tạo các role mặc định. */
    protected function seedRoles(): void
    {
        // Role global: không gắn organization_id trực tiếp trên bảng roles.
        Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => self::GUARD],
            ['organization_id' => null]
        );
        Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => self::GUARD],
            ['organization_id' => null]
        );
        Role::firstOrCreate(
            ['name' => 'Editor', 'guard_name' => self::GUARD],
            ['organization_id' => null]
        );
        Role::firstOrCreate(
            ['name' => 'Vai trò mẫu', 'guard_name' => self::GUARD],
            ['organization_id' => null]
        );

        // Chuẩn hóa dữ liệu cũ nếu còn role theo organization.
        Role::query()->update(['organization_id' => null]);
    }

    /** Gán permission cho từng role. */
    protected function assignPermissionsToRoles(): void
    {
        $allPermissionNames = $this->getAllPermissionNames();
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', self::GUARD)->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions($allPermissionNames);
        }

        $admin = Role::where('name', 'Admin')->where('guard_name', self::GUARD)->first();
        if ($admin) {
            $admin->syncPermissions($allPermissionNames);
        }

        $editorPermissionNames = $this->getEditorPermissionNames();
        $editor = Role::where('name', 'Editor')->where('guard_name', self::GUARD)->first();
        if ($editor) {
            $editor->syncPermissions($editorPermissionNames);
        }

        $samplePermissionNames = $this->getSamplePermissionNames();
        $sampleRole = Role::where('name', 'Vai trò mẫu')->where('guard_name', self::GUARD)->first();
        if ($sampleRole) {
            $sampleRole->syncPermissions($samplePermissionNames);
        }
    }

    /**
     * Tạo user cố định để đăng nhập kiểm tra và gán role mỗi role 1 user.
     */
    protected function seedFixedUsersAndAssignRoles(): void
    {
        $defaultOrganization = Organization::where('slug', 'default')->first();
        if (! $defaultOrganization) {
            return;
        }
        setPermissionsTeamId($defaultOrganization->id);

        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', self::GUARD)->first();
        $admin = Role::where('name', 'Admin')->where('guard_name', self::GUARD)->first();
        $editor = Role::where('name', 'Editor')->where('guard_name', self::GUARD)->first();
        $sampleRole = Role::where('name', 'Vai trò mẫu')->where('guard_name', self::GUARD)->first();

        $superAdminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Super Admin', 'user_name' => 'admin', 'password' => 'quandcore**11', 'status' => StatusEnum::Active->value, 'email_verified_at' => now()]
        );
        $superAdminUser->forceFill(['created_by' => 1, 'updated_by' => 1])->save();
        if ($superAdmin) {
            $superAdminUser->syncRoles([$superAdmin]);
        }

        $adminUser = User::updateOrCreate(
            ['email' => 'admin2@example.com'],
            ['name' => 'Admin Role', 'user_name' => 'admin2', 'password' => 'quandcore**11', 'status' => StatusEnum::Active->value, 'email_verified_at' => now()]
        );
        $adminUser->forceFill(['created_by' => 1, 'updated_by' => 1])->save();
        if ($admin) {
            $adminUser->syncRoles([$admin]);
        }

        $editorUser = User::updateOrCreate(
            ['email' => 'editor@example.com'],
            ['name' => 'Editor Role', 'user_name' => 'editor', 'password' => 'quandcore**11', 'status' => StatusEnum::Active->value, 'email_verified_at' => now()]
        );
        $editorUser->forceFill(['created_by' => 1, 'updated_by' => 1])->save();
        if ($editor) {
            $editorUser->syncRoles([$editor]);
        }

        $basicUser = User::updateOrCreate(
            ['email' => 'basic@example.com'],
            ['name' => 'Basic Role', 'user_name' => 'basic', 'password' => 'quandcore**11', 'status' => StatusEnum::Active->value, 'email_verified_at' => now()]
        );
        $basicUser->forceFill(['created_by' => 1, 'updated_by' => 1])->save();
        if ($sampleRole) {
            $basicUser->syncRoles([$sampleRole]);
        }
    }

    /** Lấy toàn bộ tên permission (resource.action). */
    protected function getAllPermissionNames(): array
    {
        $names = [];
        foreach (self::$PERMISSIONS as $resource => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$resource}.{$action}";
            }
        }

        return $names;
    }

    /** Permission cho role Editor: chỉ posts và post-categories. */
    protected function getEditorPermissionNames(): array
    {
        $names = [];
        foreach (['posts' => self::$PERMISSIONS['posts'], 'post-categories' => self::$PERMISSIONS['post-categories']] as $resource => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$resource}.{$action}";
            }
        }

        return $names;
    }

    /** Permission cho Vai trò mẫu: chỉ xem bài viết và danh mục (index, show, tree, stats, incrementView). */
    protected function getSamplePermissionNames(): array
    {
        return [
            'posts.stats',
            'posts.index',
            'posts.show',
            'posts.incrementView',
            'post-categories.stats',
            'post-categories.index',
            'post-categories.tree',
            'post-categories.show',
        ];
    }
}
