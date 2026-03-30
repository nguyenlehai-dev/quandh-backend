<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Requests\SwitchOrganizationRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\CaslAbilityConverter;
use App\Modules\Core\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Auth
 *
 * Xác thực: đăng nhập, đăng xuất, quên mật khẩu, đặt lại mật khẩu
 */
class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Đăng nhập
     *
     * Trả về access_token, thông tin user và danh sách organization user có thể truy cập.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email hoặc tên đăng nhập (user_name). Example: admin@example.com
     * @bodyParam password string required Mật khẩu. Example: password
     *
     * @response 200 {"success": true, "message": "Đăng nhập thành công.", "data": {"access_token": "1|xxx...", "token_type": "Bearer", "user": {"id": 1, "name": "Admin"}, "available_organizations": [{"id": 2, "name": "Sở Nội vụ"}], "current_organization_id": 2, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->email, $request->password);

        if (! $result['ok']) {
            if ($result['type'] === 'unauthorized') {
                return $this->unauthorized($result['message']);
            }

            return $this->forbidden($result['message']);
        }

        return $this->success($result['data'], 'Đăng nhập thành công.');
    }

    /**
     * Lấy thông tin user đăng nhập hiện tại kèm roles và permissions của tổ chức đang chọn.
     *
     * Dùng để Vue Casl khởi tạo ability khi refresh trang. Cần header X-Organization-Id (middleware set.permissions.team đã đặt ngữ cảnh).
     * @response 200 {"success": true, "data": {"user": {"id": 1, "name": "Admin"}, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    public function me(Request $request)
    {
        $user = $request->user();
        // Lưu lại org ID hiện tại (được gán từ middleware vào header)
        $currentOrgId = request()->header('X-Organization-Id');

        $isSuperAdmin = \Illuminate\Support\Facades\DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->whereIn('role_id', function ($query) {
                $query->select('id')->from(config('permission.table_names.roles'))
                    ->whereIn('name', ['Quản trị hệ thống', 'Super Admin']);
            })
            ->exists();

        if ($isSuperAdmin) {
            $permissions = \Spatie\Permission\Models\Permission::pluck('name')->values()->unique()->all();
            $roles = ['Quản trị hệ thống'];
        } else {
            // 1. Lấy quyền Global
            setPermissionsTeamId(null);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $globalPermissions = $user->getAllPermissions()->pluck('name');
            $globalRoles = $user->getRoleNames();

            // 2. Lấy quyền Local
            setPermissionsTeamId($currentOrgId);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $teamPermissions = $user->getAllPermissions()->pluck('name');
            $teamRoles = $user->getRoleNames();

            $permissions = $globalPermissions->merge($teamPermissions)->unique()->values()->all();
            $roles = $globalRoles->merge($teamRoles)->unique()->values()->all();
        }

        return $this->success([
            'user' => (new UserResource($user))->resolve(),
            'roles' => $roles,
            'permissions' => $permissions,
            'abilities' => CaslAbilityConverter::toCaslAbilities($permissions),
        ]);
    }

    /**
     * Đăng xuất
     *
     * Hủy token hiện tại.
     *
     * @response 200 {"success": true, "message": "Đã đăng xuất"}
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Đã đăng xuất');
    }

    /**
     * Chuyển tổ chức làm việc
     *
     * Chọn organization để frontend gắn vào header `X-Organization-Id` cho các request tiếp theo.
     *
     * @bodyParam organization_id integer required ID tổ chức muốn chuyển. Example: 2
     *
     * @response 200 {"success": true, "message": "Đã chuyển tổ chức làm việc.", "data": {"current_organization_id": 2, "current_organization": {"id": 2, "name": "Sở Nội vụ"}, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    public function switchOrganization(SwitchOrganizationRequest $request)
    {
        $result = $this->authService->switchOrganization($request->user(), (int) $request->organization_id);

        if (! $result['ok']) {
            return $this->forbidden($result['message']);
        }

        return $this->success($result['data'], 'Đã chuyển tổ chức làm việc.');
    }

    /**
     * Quên mật khẩu
     *
     * Gửi link đặt lại mật khẩu qua email.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email tài khoản. Example: user@example.com
     *
     * @response 200 {"success": true, "message": "Link reset đã được gửi vào Email"}
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $ok = $this->authService->forgotPassword($request->email);

        return $ok
            ? $this->success(null, 'Link reset đã được gửi vào Email')
            : $this->error('Không thể gửi mail', 400);
    }

    /**
     * Đặt lại mật khẩu
     *
     * Đặt mật khẩu mới dùng token từ email reset.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email tài khoản. Example: user@example.com
     * @bodyParam password string required Mật khẩu mới (tối thiểu 6 ký tự, có xác nhận). Example: newpassword123
     * @bodyParam password_confirmation string required Xác nhận mật khẩu.
     * @bodyParam token string required Token từ email reset.
     *
     * @response 200 {"success": true, "message": "Mật khẩu đã được đặt lại"}
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $ok = $this->authService->resetPassword($request->email, $request->password, $request->token);

        return $ok
            ? $this->success(null, 'Mật khẩu đã được đặt lại')
            : $this->error('Không thể đặt lại mật khẩu', 400);
    }

    /**
     * Đổi mật khẩu
     *
     * Cho phép user đang đăng nhập đổi mật khẩu bằng cách xác minh mật khẩu hiện tại.
     *
     * @bodyParam current_password string required Mật khẩu hiện tại. Example: oldpassword123
     * @bodyParam password string required Mật khẩu mới (tối thiểu 6 ký tự). Example: newpassword123
     * @bodyParam password_confirmation string required Xác nhận mật khẩu mới. Example: newpassword123
     *
     * @response 200 {"success": true, "message": "Đổi mật khẩu thành công."}
     * @response 422 {"success": false, "message": "Mật khẩu hiện tại không chính xác.", "code": "VALIDATION_ERROR"}
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Mật khẩu hiện tại không chính xác.', 422);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();

        return $this->success(null, 'Đổi mật khẩu thành công.');
    }

    /**
     * Lấy thông tin profile
     *
     * Trả về thông tin cá nhân của user đang đăng nhập, bao gồm assignments (role-organization).
     *
     * @response 200 {"success": true, "data": {"id": 1, "name": "Admin", "email": "admin@example.com", "user_name": "admin", "status": "active", "assignments": []}}
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $user->load(['assignments.organizations']);
        
        return $this->success(new UserResource($user));
    }

    /**
     * Cập nhật thông tin profile cơ bản (Tên, Email)
     *
     * Chỉ cho phép cập nhật name và email. Không cho phép tự đổi status hoặc role.
     *
     * @bodyParam name string required Tên hiển thị. Example: Nguyễn Văn A
     * @bodyParam email string required Email (duy nhất). Example: user@example.com
     *
     * @response 200 {"success": true, "data": {"id": 1, "name": "Nguyễn Văn A", "email": "user@example.com"}, "message": "Cập nhật hồ sơ thành công."}
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Note: regular users cannot update their own status or role assignments via this endpoint.
        // Role assignments must be done through UserController by an admin.

        return $this->success(new UserResource($user), 'Cập nhật hồ sơ thành công.');
    }
}
