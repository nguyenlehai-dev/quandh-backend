<?php

namespace App\Modules\Auth\Services;

use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

class AuthService
{
    public function login(string $login, string $password): array
    {
        $user = User::where('email', $login)
            ->orWhere('user_name', $login)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return [
                'ok' => false,
                'type' => 'unauthorized',
                'message' => 'Thông tin đăng nhập không chính xác',
            ];
        }

        if ($user->status !== UserStatusEnum::Active->value) {
            return [
                'ok' => false,
                'type' => 'forbidden',
                'message' => 'Tài khoản của bạn đã bị khóa',
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $organizations = $this->getAccessibleOrganizations($user);
        $currentOrganizationId = $this->resolveCurrentOrganizationId($user, $organizations);
        $rolesAndPermissions = $this->getRolesAndPermissionsForOrganization($user, $currentOrganizationId);

        return [
            'ok' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->buildAuthUserPayload($user),
                'available_organizations' => $organizations,
                'current_organization_id' => $currentOrganizationId,
                'roles' => $rolesAndPermissions['roles'],
                'permissions' => $rolesAndPermissions['permissions'],
                'abilities' => $rolesAndPermissions['abilities'],
            ],
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }
    }

    public function forgotPassword(string $email): bool
    {
        return Password::sendResetLink(['email' => $email]) === Password::RESET_LINK_SENT;
    }

    public function resetPassword(string $email, string $password, string $token): bool
    {
        $status = Password::reset(
            ['email' => $email, 'password' => $password, 'token' => $token],
            function (User $user, string $newPassword) {
                $user->forceFill(['password' => Hash::make($newPassword)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    public function switchOrganization(User $user, int $organizationId): array
    {
        $organization = Organization::query()
            ->whereKey($organizationId)
            ->where('status', 'active')
            ->first();

        if (! $organization) {
            return [
                'ok' => false,
                'type' => 'forbidden',
                'message' => 'Tổ chức không hợp lệ hoặc đã ngừng hoạt động.',
            ];
        }

        if (! $this->hasOrganizationAccess((int) $user->id, (int) $organization->id)) {
            return [
                'ok' => false,
                'type' => 'forbidden',
                'message' => 'Bạn không có quyền truy cập tổ chức đã chọn.',
            ];
        }

        $rolesAndPermissions = $this->getRolesAndPermissionsForOrganization($user, (int) $organization->id);
        $this->storeCurrentOrganizationPreference((int) $user->id, (int) $organization->id);

        return [
            'ok' => true,
            'data' => [
                'current_organization_id' => (int) $organization->id,
                'current_organization' => [
                    'id' => (int) $organization->id,
                    'name' => $organization->name,
                ],
                'roles' => $rolesAndPermissions['roles'],
                'permissions' => $rolesAndPermissions['permissions'],
                'abilities' => $rolesAndPermissions['abilities'],
            ],
        ];
    }

    protected function getAccessibleOrganizations(User $user): array
    {
        $organizationIds = $this->getAccessibleOrganizationIds((int) $user->id);
        if (empty($organizationIds)) {
            return [];
        }

        return Organization::query()
            ->whereIn('id', $organizationIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Organization $organization) => [
                'id' => (int) $organization->id,
                'name' => $organization->name,
            ])
            ->values()
            ->all();
    }

    protected function buildAuthUserPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => $user->name,
        ];
    }

    protected function resolveCurrentOrganizationId(User $user, array $organizations): ?int
    {
        if (empty($organizations)) {
            return null;
        }

        $organizationIds = collect($organizations)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // flow_direct: chỉ có một tổ chức thì backend tự xác định current_organization_id
        // và cố gắng lưu lại preference nếu hạ tầng hiện có hỗ trợ.
        if (count($organizations) === 1) {
            $organizationId = (int) $organizations[0]['id'];
            $this->storeCurrentOrganizationPreference((int) $user->id, $organizationId);

            return $organizationId;
        }

        // flow_switch: có nhiều tổ chức và đã có preference hợp lệ.
        $preferredOrganizationId = $this->getStoredCurrentOrganizationPreference((int) $user->id, $organizationIds);
        if ($preferredOrganizationId !== null) {
            return $preferredOrganizationId;
        }

        // flow_select: có nhiều tổ chức nhưng chưa có preference hợp lệ.
        return null;
    }

    protected function getStoredCurrentOrganizationPreference(int $userId, array $organizationIds): ?int
    {
        if (! $this->canUseUserPreferencesTable()) {
            return null;
        }

        $organizationId = DB::table('user_preferences')
            ->where('user_id', $userId)
            ->value('current_organization_id');

        if ($organizationId === null) {
            return null;
        }

        $organizationId = (int) $organizationId;

        return in_array($organizationId, $organizationIds, true) ? $organizationId : null;
    }

    protected function storeCurrentOrganizationPreference(int $userId, int $organizationId): void
    {
        if (! $this->canUseUserPreferencesTable()) {
            return;
        }

        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => $userId],
            ['current_organization_id' => $organizationId]
        );
    }

    protected function canUseUserPreferencesTable(): bool
    {
        if (! Schema::hasTable('user_preferences')) {
            return false;
        }

        return Schema::hasColumns('user_preferences', ['user_id', 'current_organization_id']);
    }

    protected function getAccessibleOrganizationIds(int $userId): array
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'organization_id';
        $modelType = \App\Modules\Core\Models\User::class;

        $roleOrgIds = DB::table($tableNames['model_has_roles'] ?? 'model_has_roles')
            ->where($modelMorphKey, $userId)
            ->where('model_type', $modelType)
            ->whereNotNull($teamForeignKey)
            ->pluck($teamForeignKey)
            ->map(fn ($id) => (int) $id)
            ->all();

        $permissionOrgIds = DB::table($tableNames['model_has_permissions'] ?? 'model_has_permissions')
            ->where($modelMorphKey, $userId)
            ->where('model_type', $modelType)
            ->whereNotNull($teamForeignKey)
            ->pluck($teamForeignKey)
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($roleOrgIds, $permissionOrgIds)));
    }

    protected function hasOrganizationAccess(int $userId, int $organizationId): bool
    {
        return in_array($organizationId, $this->getAccessibleOrganizationIds($userId), true);
    }

    /**
     * Lấy danh sách vai trò và quyền hạn của user trong tổ chức, dùng cho Vue Casl.
     */
    protected function getRolesAndPermissionsForOrganization(User $user, ?int $organizationId): array
    {
        if ($organizationId === null) {
            return ['roles' => [], 'permissions' => [], 'abilities' => []];
        }

        setPermissionsTeamId($organizationId);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        // getAllPermissions() = direct + từ vai trò; getPermissionNames() chỉ direct
        $permissions = $user->getAllPermissions()->pluck('name')->values()->unique()->all();

        return [
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $permissions,
            'abilities' => CaslAbilityConverter::toCaslAbilities($permissions),
        ];
    }
}
