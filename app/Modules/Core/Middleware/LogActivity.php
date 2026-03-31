<?php

namespace App\Modules\Core\Middleware;

use App\Modules\Core\Models\LogActivity as LogActivityModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ghi nhật ký truy cập của người dùng vào bảng log_activities.
 *
 * Khi thêm resource hoặc action mới vào API: cập nhật resourceLabel() (resource => nhãn),
 * actionLabels trong descriptionFromRouteName(), pathActions trong descriptionFromPath(),
 * và route parameters trong descriptionFromRouteName() (params) để mô tả chính xác.
 */
class LogActivity
{
    /** Các trường nhạy cảm không lưu vào request_data. */
    protected static array $excludedRequestKeys = [
        'password', 'password_confirmation', '_token', 'token',
        'email_smtp_password', 'sms_password', 'zalo_password', 'chat_api_key',
        'api_gemini_token', 'api_deepseek_token', 'api_chatgpt_token',
        'api_firebase_token', 'api_google_maps_token',
    ];

    /** GET actions không cần ghi log (giảm ~80% DB writes). */
    protected static array $skipGetActions = [
        'index', 'show', 'stats', 'tree', 'public', 'publicOptions',
    ];

    /** Paths không tạo notification. */
    protected static array $skipNotificationPaths = [
        'user/notifications', 'user/notification-preferences', 'user/change-password',
        'auth/login', 'auth/logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Terminable middleware — chạy SAU khi response đã gửi cho client.
     * Client nhận response ngay, log ghi bất đồng bộ.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldLog($request)) {
            return;
        }

        $this->log($request, $response->getStatusCode());
    }

    protected function shouldLog(Request $request): bool
    {
        // Skip excluded paths
        $excludedPaths = ['/up'];
        foreach ($excludedPaths as $path) {
            if (str_starts_with($request->path(), ltrim($path, '/'))) {
                return false;
            }
        }

        // Skip GET read-only requests (index, show, stats, tree, public)
        if ($request->isMethod('GET')) {
            $action = $this->resolveGetAction($request);
            if ($action && in_array($action, self::$skipGetActions, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Xác định action GET kể cả khi route chưa được đặt name.
     */
    protected function resolveGetAction(Request $request): ?string
    {
        $routeName = $request->route()?->getName();
        if ($routeName) {
            return last(explode('.', $routeName));
        }

        $path = trim($request->path(), '/');
        $segments = array_values(array_filter(explode('/', $path)));

        if (($segments[0] ?? null) === 'api') {
            array_shift($segments);
        }

        $resource = $segments[0] ?? null;
        $subAction = $segments[1] ?? null;

        if (! $resource) {
            return null;
        }

        if (! $subAction) {
            return 'index';
        }

        $pathActions = [
            'stats' => 'stats',
            'tree' => 'tree',
            'public' => 'public',
            'public-options' => 'publicOptions',
        ];

        if (isset($pathActions[$subAction])) {
            return $pathActions[$subAction];
        }

        if (count($segments) === 2 && ! in_array($subAction, ['export', 'template', 'results'], true)) {
            return 'show';
        }

        return null;
    }

    protected function log(Request $request, int $statusCode): void
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $userType = $user ? class_basename($user) : 'Guest';
            $userId = $user?->id;
            $organizationId = function_exists('getPermissionsTeamId') ? getPermissionsTeamId() : null;
            $description = $this->buildDescription($request);

            LogActivityModel::create([
                'description' => $description,
                'user_type' => $userType,
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'route' => $request->fullUrl(),
                'method_type' => $request->method(),
                'status_code' => $statusCode,
                'ip_address' => $request->ip() ?? '0.0.0.0',
                'country' => $this->resolveCountry($request),
                'user_agent' => $request->userAgent(),
                'request_data' => $this->sanitizeRequestData($request),
            ]);

            // Tạo notification cho write operations thành công
            if ($user && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']) && $statusCode >= 200 && $statusCode < 300) {
                $this->createNotification($user, $request->method(), $description);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Tạo database notification cho user khi thực hiện thao tác ghi.
     */
    protected function createNotification($user, string $method, string $description): void
    {
        // Skip notification cho một số paths
        $path = trim(request()->path(), '/');
        $apiPath = str_starts_with($path, 'api/') ? substr($path, 4) : $path;
        foreach (self::$skipNotificationPaths as $skip) {
            if (str_starts_with($apiPath, $skip)) {
                return;
            }
        }

        $iconMap = [
            'POST' => 'tabler-circle-plus',
            'PUT' => 'tabler-edit',
            'PATCH' => 'tabler-edit',
            'DELETE' => 'tabler-trash',
        ];

        $colorMap = [
            'POST' => 'success',
            'PUT' => 'warning',
            'PATCH' => 'warning',
            'DELETE' => 'error',
        ];

        try {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'App\\Notifications\\ActivityNotification',
                'notifiable_type' => get_class($user),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $description,
                    'subtitle' => 'Thao tác thành công',
                    'icon' => $iconMap[$method] ?? 'tabler-bell',
                    'color' => $colorMap[$method] ?? 'primary',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function buildDescription(Request $request): string
    {
        $routeName = $request->route()?->getName();
        if ($routeName) {
            return $this->descriptionFromRouteName($routeName, $request);
        }

        return $this->descriptionFromPath($request);
    }

    /** Mô tả từ path khi không có route name (vd: GET api/posts → Truy cập danh sách bài viết). */
    protected function descriptionFromPath(Request $request): string
    {
        $path = trim($request->path(), '/');
        $segments = explode('/', $path);
        $method = $request->method();

        if (($segments[0] ?? '') === 'api') {
            array_shift($segments);
        }

        $resource = $segments[0] ?? '';
        $sub = $segments[1] ?? null;

        // Auth: api/auth/login, api/auth/forgot-password...
        if ($resource === 'auth') {
            $authLabels = ['login' => 'Đăng nhập', 'logout' => 'Đăng xuất', 'forgot-password' => 'Quên mật khẩu', 'reset-password' => 'Đặt lại mật khẩu'];

            return $authLabels[$sub] ?? "Xác thực: {$sub}";
        }

        // Settings: api/settings/public
        if ($resource === 'settings' && $sub === 'public') {
            return 'Xem cấu hình công khai';
        }

        // Action trong path: export, import, stats, bulk-delete, delete-by-date, clear...
        $pathActions = [
            'export' => 'Xuất dữ liệu',
            'import' => 'Nhập dữ liệu',
            'stats' => 'Xem thống kê',
            'public' => 'Xem dữ liệu công khai',
            'public-options' => 'Xem dữ liệu dropdown công khai',
            'bulk-delete' => 'Xóa hàng loạt',
            'bulk-status' => 'Cập nhật trạng thái hàng loạt',
            'tree' => 'Xem cây',
            'delete-by-date' => 'Xóa theo khoảng thời gian',
            'clear' => 'Xóa toàn bộ',
            'checkin' => 'Điểm danh',
            'reorder' => 'Sắp xếp lại',
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
            'open' => 'Mở biểu quyết',
            'close' => 'Đóng biểu quyết',
            'vote' => 'Bỏ phiếu',
            'results' => 'Xem kết quả biểu quyết',
        ];
        if ($sub && isset($pathActions[$sub])) {
            return $pathActions[$sub].' '.$this->resourceLabel(str_replace('-', '_', $resource));
        }

        $resourceLabel = $this->resourceLabel(str_replace('-', '_', $resource));
        $id = $sub && is_numeric($sub) ? $sub : null;

        $labels = [
            'GET' => $id ? 'Xem chi tiết' : 'Truy cập danh sách',
            'POST' => 'Tạo mới',
            'PUT' => 'Cập nhật',
            'PATCH' => 'Cập nhật',
            'DELETE' => 'Xóa',
        ];
        $actionLabel = $labels[$method] ?? $method;
        $suffix = $id ? " #{$id}" : '';

        return trim("{$actionLabel} {$resourceLabel}{$suffix}") ?: "{$method} /{$path}";
    }

    protected function descriptionFromRouteName(string $routeName, Request $request): string
    {
        $parts = explode('.', $routeName);
        $resource = $parts[0] ?? '';
        $action = $parts[1] ?? 'access';

        $actionLabels = [
            'index' => 'Truy cập danh sách',
            'show' => 'Xem chi tiết',
            'store' => 'Tạo mới',
            'update' => 'Cập nhật',
            'destroy' => 'Xóa',
            'stats' => 'Xem thống kê',
            'tree' => 'Xem cây',
            'export' => 'Xuất dữ liệu',
            'import' => 'Nhập dữ liệu',
            'changeStatus' => 'Đổi trạng thái',
            'bulkDestroy' => 'Xóa hàng loạt',
            'bulkUpdateStatus' => 'Cập nhật trạng thái hàng loạt',
            'incrementView' => 'Tăng lượt xem',
            'destroyByDate' => 'Xóa theo khoảng thời gian',
            'destroyAll' => 'Xóa toàn bộ',
            'public' => 'Xem dữ liệu công khai',
            'checkin' => 'Điểm danh',
            'reorder' => 'Sắp xếp lại',
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
            'open' => 'Mở biểu quyết',
            'close' => 'Đóng biểu quyết',
            'vote' => 'Bỏ phiếu',
            'results' => 'Xem kết quả biểu quyết',
        ];

        $actionLabel = $actionLabels[$action] ?? $action;
        $resourceLabel = $this->resourceLabel($resource);

        $params = $request->route()?->parameters() ?? [];
        $id = $params['user']
            ?? $params['post']
            ?? $params['organization']
            ?? $params['category']
            ?? $params['role']
            ?? $params['logActivity']
            ?? $params['document']
            ?? $params['documentType']
            ?? $params['issuingAgency']
            ?? $params['issuingLevel']
            ?? $params['documentSigner']
            ?? $params['documentField']
            ?? $params['meeting']
            ?? $params['participant']
            ?? $params['agenda']
            ?? $params['conclusion']
            ?? $params['note']
            ?? $params['speechRequest']
            ?? $params['voting']
            ?? $params['id']
            ?? null;
        $suffix = $id ? ' #'.(is_object($id) ? $id->getKey() : $id) : '';

        return trim("{$actionLabel} {$resourceLabel}{$suffix}");
    }

    protected function resourceLabel(string $resource): string
    {
        $resource = str_replace('_', '-', $resource);
        $labels = [
            'users' => 'người dùng',
            'posts' => 'bài viết',
            'post-categories' => 'danh mục bài viết',
            'permissions' => 'quyền',
            'roles' => 'vai trò',
            'organizations' => 'tổ chức',
            'auth' => 'xác thực',
            'log-activities' => 'nhật ký truy cập',
            'documents' => 'văn bản',
            'document-types' => 'loại văn bản',
            'issuing-agencies' => 'cơ quan ban hành',
            'issuing-levels' => 'cấp ban hành',
            'document-signers' => 'người ký',
            'document-fields' => 'lĩnh vực',
            'settings' => 'cấu hình hệ thống',
            'meetings' => 'cuộc họp',
            'meeting-participants' => 'thành viên cuộc họp',
            'meeting-agendas' => 'chương trình nghị sự',
            'meeting-documents' => 'tài liệu cuộc họp',
            'meeting-conclusions' => 'kết luận cuộc họp',
            'meeting-personal-notes' => 'ghi chú cá nhân',
            'meeting-speech-requests' => 'đăng ký phát biểu',
            'meeting-votings' => 'biểu quyết',
        ];

        return $labels[$resource] ?? str_replace('-', ' ', $resource);
    }

    protected function resolveCountry(Request $request): ?string
    {
        $ip = $request->ip();
        if (! $ip || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        try {
            $position = Location::get($ip);

            return $position ? $position->countryName : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    protected function sanitizeRequestData(Request $request): ?array
    {
        $data = array_merge($request->query(), $request->except(self::$excludedRequestKeys));

        if (empty($data)) {
            return null;
        }

        // Giới hạn kích thước để tránh lưu quá nhiều dữ liệu
        $encoded = json_encode($data);
        if (strlen($encoded) > 65535) {
            return ['_truncated' => true, 'size' => strlen($encoded)];
        }

        return $data;
    }
}
