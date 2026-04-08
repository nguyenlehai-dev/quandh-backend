<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

class MeetingPermissionSeeder extends Seeder
{
    protected const GUARD = 'web';

    protected array $permissions = [
        'meetings' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus', 'export', 'import',
        ],
        'meeting-types' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus',
        ],
        'attendee-groups' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus',
        ],
        'meeting-document-types' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus',
        ],
        'meeting-document-fields' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus',
        ],
        'meeting-document-signers' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus',
        ],
        'meeting-issuing-agencies' => [
            'stats', 'index', 'show', 'store', 'update', 'destroy',
            'bulkDestroy', 'bulkUpdateStatus', 'changeStatus',
        ],
    ];

    protected array $labels = [
        'meetings' => 'Cuộc họp',
        'meeting-types' => 'Loại cuộc họp',
        'attendee-groups' => 'Nhóm thành phần tham dự',
        'meeting-document-types' => 'Loại tài liệu cuộc họp',
        'meeting-document-fields' => 'Lĩnh vực tài liệu cuộc họp',
        'meeting-document-signers' => 'Người ký tài liệu cuộc họp',
        'meeting-issuing-agencies' => 'Cơ quan ban hành tài liệu cuộc họp',
    ];

    protected array $actionLabels = [
        'stats' => 'Thống kê',
        'index' => 'Danh sách',
        'show' => 'Chi tiết',
        'store' => 'Tạo mới',
        'update' => 'Cập nhật',
        'destroy' => 'Xóa',
        'bulkDestroy' => 'Xóa hàng loạt',
        'bulkUpdateStatus' => 'Cập nhật trạng thái hàng loạt',
        'changeStatus' => 'Đổi trạng thái',
        'export' => 'Xuất Excel',
        'import' => 'Nhập Excel',
    ];

    public function run(): void
    {
        $sortOrder = 1000;

        foreach ($this->permissions as $resource => $actions) {
            $label = $this->labels[$resource] ?? $resource;
            $group = Permission::updateOrCreate(
                ['name' => "group:{$resource}", 'guard_name' => self::GUARD],
                ['description' => $label, 'sort_order' => $sortOrder++, 'parent_id' => null]
            );

            foreach ($actions as $index => $action) {
                Permission::updateOrCreate(
                    ['name' => "{$resource}.{$action}", 'guard_name' => self::GUARD],
                    [
                        'description' => $label.' - '.($this->actionLabels[$action] ?? $action),
                        'sort_order' => $index,
                        'parent_id' => $group->id,
                    ]
                );
            }
        }

        $allMeetingPermissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->where(function ($query) {
                foreach (array_keys($this->permissions) as $resource) {
                    $query->orWhere('name', 'like', "{$resource}.%");
                }
            })
            ->get();

        Role::query()
            ->whereIn('name', ['Super Admin', 'Admin'])
            ->where('guard_name', self::GUARD)
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($allMeetingPermissions));

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
