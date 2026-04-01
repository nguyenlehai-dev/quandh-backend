<?php

namespace Database\Seeders;

use App\Modules\Core\Enums\StatusEnum;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthFlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OrganizationDemoSeeder::class);

        $roleIds = Role::query()->pluck('id', 'name');
        $orgIds = Organization::query()->pluck('id', 'slug');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'organization_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $now = now();

        $users = [
            [
                'user_name' => 'flow_direct',
                'name' => 'Flow Direct',
                'email' => 'flow.direct@example.com',
                'password' => 'quandcore**11',
                'assignments' => [
                    'Editor' => ['so-noi-vu'],
                ],
                'current_organization_slug' => 'so-noi-vu',
            ],
            [
                'user_name' => 'flow_select',
                'name' => 'Flow Select Org',
                'email' => 'flow.select@example.com',
                'password' => 'quandcore**11',
                'assignments' => [
                    'Editor' => ['so-noi-vu', 'ubnd-thanh-pho'],
                ],
                'current_organization_slug' => null,
            ],
            [
                'user_name' => 'flow_switch',
                'name' => 'Flow Switch Org',
                'email' => 'flow.switch@example.com',
                'password' => 'quandcore**11',
                'assignments' => [
                    'Vai trò mẫu' => ['ubnd-thanh-pho', 'trung-tam-cong-nghe'],
                ],
                'current_organization_slug' => 'trung-tam-cong-nghe',
            ],
        ];

        foreach ($users as $config) {
            $user = User::updateOrCreate(
                ['user_name' => $config['user_name']],
                [
                    'name' => $config['name'],
                    'email' => $config['email'],
                    'password' => $config['password'],
                    'status' => StatusEnum::Active->value,
                    'email_verified_at' => $now,
                ],
            );

            $user->forceFill(['created_by' => null, 'updated_by' => null])->save();

            DB::table($modelHasRolesTable)
                ->where($modelMorphKey, (int) $user->id)
                ->where('model_type', User::class)
                ->delete();

            foreach ($config['assignments'] as $roleName => $organizationSlugs) {
                $roleId = $roleIds[$roleName] ?? null;
                if (! $roleId) {
                    continue;
                }

                foreach ($organizationSlugs as $organizationSlug) {
                    $organizationId = $orgIds[$organizationSlug] ?? null;
                    if (! $organizationId) {
                        continue;
                    }

                    DB::table($modelHasRolesTable)->insertOrIgnore([
                        'role_id' => (int) $roleId,
                        'model_type' => User::class,
                        $modelMorphKey => (int) $user->id,
                        $teamForeignKey => (int) $organizationId,
                    ]);
                }
            }

            DB::table('user_preferences')->updateOrInsert(
                ['user_id' => (int) $user->id],
                [
                    'current_organization_id' => $config['current_organization_slug']
                        ? ($orgIds[$config['current_organization_slug']] ?? null)
                        : null,
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
