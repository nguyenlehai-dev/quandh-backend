<?php

namespace Tests\Feature;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthPublishedDocsParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_login_auto_selects_the_only_accessible_organization(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'direct@example.com',
            'user_name' => 'direct-user',
            'password' => 'password',
        ]);
        $organization = Organization::factory()->create([
            'name' => 'Direct Org',
            'status' => 'active',
        ]);

        $this->attachUserToOrganization($user, $organization, 'admin', ['users.index']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'direct@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', $user->name)
            ->assertJsonPath('data.current_organization_id', $organization->id)
            ->assertJsonPath('data.available_organizations.0.id', $organization->id)
            ->assertJsonPath('data.available_organizations.0.name', 'Direct Org')
            ->assertJsonPath('data.roles.0', 'admin')
            ->assertJsonPath('data.permissions.0', 'users.index')
            ->assertJsonPath('data.abilities.0.action', 'index')
            ->assertJsonPath('data.abilities.0.subject', 'User');

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'current_organization_id' => $organization->id,
        ]);
    }

    public function test_login_returns_null_when_user_has_multiple_organizations_without_preference(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'select@example.com',
            'user_name' => 'select-user',
            'password' => 'password',
        ]);
        $organizationA = Organization::factory()->create([
            'name' => 'A Organization',
            'status' => 'active',
        ]);
        $organizationB = Organization::factory()->create([
            'name' => 'B Organization',
            'status' => 'active',
        ]);

        $this->attachUserToOrganization($user, $organizationA, 'admin');
        $this->attachUserToOrganization($user, $organizationB, 'editor');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'select@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_organization_id', null)
            ->assertJsonPath('data.roles', [])
            ->assertJsonPath('data.permissions', [])
            ->assertJsonPath('data.abilities', []);

        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $user->id,
        ]);
    }

    public function test_login_accepts_user_name_in_email_field(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'username-login@example.com',
            'user_name' => 'published-docs-user',
            'password' => 'password',
        ]);
        $organization = Organization::factory()->create([
            'name' => 'Username Org',
            'status' => 'active',
        ]);

        $this->attachUserToOrganization($user, $organization, 'admin');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'published-docs-user',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_organization_id', $organization->id);
    }

    public function test_login_uses_valid_stored_preference_when_user_has_multiple_organizations(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'switch@example.com',
            'user_name' => 'switch-user',
            'password' => 'password',
        ]);
        $organizationA = Organization::factory()->create([
            'name' => 'A Organization',
            'status' => 'active',
        ]);
        $organizationB = Organization::factory()->create([
            'name' => 'B Organization',
            'status' => 'active',
        ]);

        $this->attachUserToOrganization($user, $organizationA, 'admin', ['users.index']);
        $this->attachUserToOrganization($user, $organizationB, 'editor', ['users.store']);

        DB::table('user_preferences')->insert([
            'user_id' => $user->id,
            'current_organization_id' => $organizationB->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'switch@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_organization_id', $organizationB->id)
            ->assertJsonPath('data.roles.0', 'editor')
            ->assertJsonPath('data.permissions.0', 'users.store')
            ->assertJsonPath('data.abilities.0.action', 'store')
            ->assertJsonPath('data.abilities.0.subject', 'User');
    }

    public function test_switch_organization_persists_preference_and_returns_new_context(): void
    {
        $user = User::factory()->active()->create();
        $organizationA = Organization::factory()->create([
            'name' => 'A Organization',
            'status' => 'active',
        ]);
        $organizationB = Organization::factory()->create([
            'name' => 'B Organization',
            'status' => 'active',
        ]);

        $this->attachUserToOrganization($user, $organizationA, 'admin', ['users.index']);
        $this->attachUserToOrganization($user, $organizationB, 'editor', ['users.store']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/switch-organization', [
            'organization_id' => $organizationB->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_organization_id', $organizationB->id)
            ->assertJsonPath('data.current_organization.id', $organizationB->id)
            ->assertJsonPath('data.current_organization.name', 'B Organization')
            ->assertJsonPath('data.roles.0', 'editor')
            ->assertJsonPath('data.permissions.0', 'users.store')
            ->assertJsonPath('data.abilities.0.subject', 'User');

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'current_organization_id' => $organizationB->id,
        ]);
    }

    public function test_me_returns_roles_permissions_and_abilities_for_requested_organization(): void
    {
        $user = User::factory()->active()->create();
        $organization = Organization::factory()->create([
            'name' => 'Auth Context Org',
            'status' => 'active',
        ]);

        $this->attachUserToOrganization($user, $organization, 'admin', ['users.index']);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', $user->name)
            ->assertJsonPath('data.roles.0', 'admin')
            ->assertJsonPath('data.permissions.0', 'users.index')
            ->assertJsonPath('data.abilities.0.action', 'index')
            ->assertJsonPath('data.abilities.0.subject', 'User');
    }

    public function test_forgot_password_returns_success_and_creates_reset_token(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'forgot@example.com',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Link reset đã được gửi vào Email');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_returns_success_and_updates_password(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'reset@example.com',
            'password' => 'old-password',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => $token,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Mật khẩu đã được đặt lại');

        $user->refresh();

        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_logout_does_not_require_x_organization_id_header(): void
    {
        $user = User::factory()->active()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Đã đăng xuất');
    }

    public function test_me_requires_x_organization_id_header(): void
    {
        $user = User::factory()->active()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    protected function attachUserToOrganization(
        User $user,
        Organization $organization,
        string $roleName,
        array $permissionNames = []
    ): void {
        $role = Role::query()->create([
            'name' => $roleName,
            'guard_name' => 'web',
            'organization_id' => $organization->id,
        ]);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        setPermissionsTeamId($organization->id);
        $user->assignRole($role);
        setPermissionsTeamId(null);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
