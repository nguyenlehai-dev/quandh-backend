<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $organizations = [
            [
                'slug' => 'so-noi-vu',
                'name' => 'Sở Nội vụ',
                'description' => 'Khối quản lý tổ chức bộ máy, biên chế và cán bộ.',
                'status' => 'active',
                'parent_slug' => null,
                'sort_order' => 10,
            ],
            [
                'slug' => 'ubnd-thanh-pho',
                'name' => 'UBND Thành phố',
                'description' => 'Khối điều hành tổng hợp cấp thành phố.',
                'status' => 'active',
                'parent_slug' => null,
                'sort_order' => 20,
            ],
            [
                'slug' => 'phong-to-chuc-can-bo',
                'name' => 'Phòng Tổ chức Cán bộ',
                'description' => 'Đơn vị chuyên trách công tác tổ chức, nhân sự.',
                'status' => 'active',
                'parent_slug' => 'so-noi-vu',
                'sort_order' => 1,
            ],
            [
                'slug' => 'trung-tam-cong-nghe',
                'name' => 'Trung tâm Công nghệ',
                'description' => 'Đơn vị phụ trách hạ tầng, phần mềm và chuyển đổi số.',
                'status' => 'active',
                'parent_slug' => 'ubnd-thanh-pho',
                'sort_order' => 1,
            ],
        ];

        foreach ($organizations as $org) {
            DB::table('organizations')->updateOrInsert(
                ['slug' => $org['slug']],
                [
                    'name' => $org['name'],
                    'description' => $org['description'],
                    'status' => $org['status'],
                    'sort_order' => $org['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $orgIdBySlug = DB::table('organizations')->pluck('id', 'slug');

        foreach ($organizations as $org) {
            DB::table('organizations')
                ->where('slug', $org['slug'])
                ->update([
                    'parent_id' => $org['parent_slug'] ? $orgIdBySlug[$org['parent_slug']] ?? null : null,
                    'updated_at' => $now,
                ]);
        }

        $roleIds = Role::query()->pluck('id', 'name');
        $users = User::query()
            ->whereIn('user_name', ['admin2', 'editor', 'basic', 'tk_dbtram', 'dt_chdang'])
            ->get()
            ->keyBy('user_name');

        $assignments = [
            'admin2' => [
                'Admin' => ['default', 'so-noi-vu', 'ubnd-thanh-pho'],
            ],
            'editor' => [
                'Editor' => ['default', 'so-noi-vu', 'phong-to-chuc-can-bo'],
            ],
            'basic' => [
                'Vai trò mẫu' => ['default', 'trung-tam-cong-nghe'],
            ],
            'tk_dbtram' => [
                'Editor' => ['so-noi-vu'],
            ],
            'dt_chdang' => [
                'Vai trò mẫu' => ['ubnd-thanh-pho'],
            ],
        ];

        foreach ($assignments as $userName => $roleMap) {
            $user = $users->get($userName);
            if (! $user) {
                continue;
            }

            foreach ($roleMap as $roleName => $organizationSlugs) {
                $roleId = $roleIds[$roleName] ?? null;
                if (! $roleId) {
                    continue;
                }

                foreach ($organizationSlugs as $organizationSlug) {
                    $organizationId = $orgIdBySlug[$organizationSlug] ?? null;
                    if (! $organizationId) {
                        continue;
                    }

                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => (int) $roleId,
                        'model_type' => User::class,
                        'model_id' => (int) $user->id,
                        'organization_id' => (int) $organizationId,
                    ]);
                }
            }
        }

        $preferences = [
            'admin2' => 'ubnd-thanh-pho',
            'editor' => 'so-noi-vu',
            'basic' => 'trung-tam-cong-nghe',
        ];

        foreach ($preferences as $userName => $organizationSlug) {
            $user = $users->get($userName);
            $organizationId = $orgIdBySlug[$organizationSlug] ?? null;

            if (! $user || ! $organizationId) {
                continue;
            }

            DB::table('user_preferences')->updateOrInsert(
                ['user_id' => (int) $user->id],
                [
                    'current_organization_id' => (int) $organizationId,
                    'notify_email' => true,
                    'notify_system' => true,
                    'notify_meeting_reminder' => true,
                    'notify_vote' => true,
                    'notify_document' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
}
