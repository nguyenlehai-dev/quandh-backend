<?php

namespace App\Modules\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use App\Modules\Auth\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\ResetPasswordRequest;
use App\Modules\Auth\Requests\SwitchOrganizationRequest;
use App\Modules\Auth\Requests\UpdateProfileRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\CaslAbilityConverter;
use App\Modules\Core\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Auth
 *
 * Xac thuc, thong tin nguoi dung hien tai, ho so ca nhan, va chuyen to chuc lam viec.
 */
class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Dang nhap
     *
     * Tra ve access token, thong tin nguoi dung, danh sach to chuc co the truy cap,
     * vai tro, quyen han va abilities cho frontend.
     *
     * @unauthenticated
     * @bodyParam email string required Email hoac ten dang nhap. Example: admin@example.com
     * @bodyParam password string required Mat khau dang nhap. Example: password
     * @response 200 {"success": true, "message": "Dang nhap thanh cong.", "data": {"access_token": "1|xxx...", "token_type": "Bearer", "user": {"id": 1, "name": "Admin"}, "available_organizations": [{"id": 2, "name": "So Noi vu"}], "current_organization_id": 2, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
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

        return $this->success($result['data'], 'ÄÄƒng nháº­p thÃ nh cÃ´ng.');
    }

    /**
     * Lay thong tin nguoi dung hien tai
     *
     * Tra ve thong tin user dang dang nhap kem roles, permissions va CASL abilities
     * trong to chuc dang chon.
     *
     * @response 200 {"success": true, "data": {"user": {"id": 1, "name": "Admin"}, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $currentOrgId = request()->header('X-Organization-Id');

        $isSuperAdmin = \Illuminate\Support\Facades\DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
            ->where('model_id', $user->id)
            ->where('model_type', get_class($user))
            ->whereIn('role_id', function ($query) {
                $query->select('id')->from(config('permission.table_names.roles'))
                    ->whereIn('name', ['Quáº£n trá»‹ há»‡ thá»‘ng', 'Super Admin']);
            })
            ->exists();

        if ($isSuperAdmin) {
            $permissions = \Spatie\Permission\Models\Permission::pluck('name')->values()->unique()->all();
            $roles = ['Quáº£n trá»‹ há»‡ thá»‘ng'];
        } else {
            setPermissionsTeamId(null);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $globalPermissions = $user->getAllPermissions()->pluck('name');
            $globalRoles = $user->getRoleNames();

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
     * Dang xuat
     *
     * Huy token hien tai cua nguoi dung dang dang nhap.
     *
     * @response 200 {"success": true, "message": "Da dang xuat"}
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'ÄÃ£ Ä‘Äƒng xuáº¥t');
    }

    /**
     * Chuyen to chuc lam viec
     *
     * Chon to chuc hien tai de frontend gui kem header X-Organization-Id
     * cho cac request duoc phan quyen theo to chuc.
     *
     * @bodyParam organization_id integer required ID to chuc muon chuyen. Example: 2
     * @response 200 {"success": true, "message": "Da chuyen to chuc lam viec.", "data": {"current_organization_id": 2, "current_organization": {"id": 2, "name": "So Noi vu"}, "roles": ["admin"], "permissions": ["users.index", "users.store"], "abilities": [{"action": "index", "subject": "User"}, {"action": "store", "subject": "User"}]}}
     */
    public function switchOrganization(SwitchOrganizationRequest $request)
    {
        $result = $this->authService->switchOrganization($request->user(), (int) $request->organization_id);

        if (! $result['ok']) {
            return $this->forbidden($result['message']);
        }

        return $this->success($result['data'], 'ÄÃ£ chuyá»ƒn tá»• chá»©c lÃ m viá»‡c.');
    }

    /**
     * Quen mat khau
     *
     * Gui link dat lai mat khau qua email.
     *
     * @unauthenticated
     * @bodyParam email string required Email tai khoan. Example: user@example.com
     * @response 200 {"success": true, "message": "Link reset da duoc gui vao email"}
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $ok = $this->authService->forgotPassword($request->email);

        return $ok
            ? $this->success(null, 'Link reset Ä‘Ã£ Ä‘Æ°á»£c gá»­i vÃ o Email')
            : $this->error('KhÃ´ng thá»ƒ gá»­i mail', 400);
    }

    /**
     * Dat lai mat khau
     *
     * Dat mat khau moi bang token tu email reset.
     *
     * @unauthenticated
     * @bodyParam email string required Email tai khoan. Example: user@example.com
     * @bodyParam password string required Mat khau moi. Example: newpassword123
     * @bodyParam password_confirmation string required Xac nhan mat khau moi. Example: newpassword123
     * @bodyParam token string required Token reset mat khau. Example: sample-reset-token
     * @response 200 {"success": true, "message": "Mat khau da duoc dat lai"}
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $ok = $this->authService->resetPassword($request->email, $request->password, $request->token);

        return $ok
            ? $this->success(null, 'Máº­t kháº©u Ä‘Ã£ Ä‘Æ°á»£c Ä‘áº·t láº¡i')
            : $this->error('KhÃ´ng thá»ƒ Ä‘áº·t láº¡i máº­t kháº©u', 400);
    }

    /**
     * Doi mat khau
     *
     * Nguoi dung dang dang nhap doi mat khau bang cach xac thuc mat khau hien tai.
     *
     * @bodyParam current_password string required Mat khau hien tai. Example: oldpassword123
     * @bodyParam password string required Mat khau moi. Example: newpassword123
     * @bodyParam password_confirmation string required Xac nhan mat khau moi. Example: newpassword123
     * @response 200 {"success": true, "message": "Doi mat khau thanh cong."}
     * @response 422 {"success": false, "message": "Mat khau hien tai khong chinh xac.", "code": "VALIDATION_ERROR"}
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->error('Máº­t kháº©u hiá»‡n táº¡i khÃ´ng chÃ­nh xÃ¡c.', 422);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();

        return $this->success(null, 'Äá»•i máº­t kháº©u thÃ nh cÃ´ng.');
    }

    /**
     * Lay profile
     *
     * Tra ve thong tin ho so cua nguoi dung dang dang nhap, bao gom assignments.
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
     * Cap nhat profile
     *
     * Chi cho phep cap nhat ten hien thi va email cua nguoi dung dang dang nhap.
     *
     * @bodyParam name string required Ten hien thi. Example: Nguyen Van A
     * @bodyParam email string required Email duy nhat. Example: user@example.com
     * @response 200 {"success": true, "data": {"id": 1, "name": "Nguyen Van A", "email": "user@example.com"}, "message": "Cap nhat ho so thanh cong."}
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return $this->success(new UserResource($user), 'Cáº­p nháº­t há»“ sÆ¡ thÃ nh cÃ´ng.');
    }
}
