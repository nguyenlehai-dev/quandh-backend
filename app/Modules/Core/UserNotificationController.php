<?php

namespace App\Modules\Core;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\MarkUserNotificationsRequest;
use App\Modules\Core\Requests\UpdateUserNotificationPreferencesRequest;
use App\Modules\Core\Services\UserNotificationService;
use Illuminate\Http\Request;

/**
 * @group Core - User Notifications
 * @header X-Organization-Id 1
 *
 * Tùy chọn thông báo và danh sách thông báo của người dùng hiện tại.
 */
class UserNotificationController extends Controller
{
    public function __construct(private UserNotificationService $userNotificationService) {}

    /**
     * Lấy cấu hình thông báo của người dùng hiện tại
     *
     * @response 200 {"success": true, "data": {"notify_email": true, "notify_system": true, "notify_meeting_reminder": true, "notify_vote": true, "notify_document": false}}
     */
    public function preferences(Request $request)
    {
        return $this->success($this->userNotificationService->getPreferences($request->user()));
    }

    /**
     * Cập nhật cấu hình thông báo của người dùng hiện tại
     *
     * @bodyParam notify_email boolean Bật/tắt thông báo email. Example: true
     * @bodyParam notify_system boolean Bật/tắt thông báo hệ thống. Example: true
     * @bodyParam notify_meeting_reminder boolean Bật/tắt nhắc lịch họp. Example: true
     * @bodyParam notify_vote boolean Bật/tắt nhắc biểu quyết. Example: true
     * @bodyParam notify_document boolean Bật/tắt nhắc tài liệu. Example: false
     *
     * @response 200 {"success": true, "message": "Cập nhật cấu hình thông báo thành công."}
     */
    public function updatePreferences(UpdateUserNotificationPreferencesRequest $request)
    {
        $this->userNotificationService->updatePreferences($request->user(), $request->validated());

        return $this->success(null, 'Cập nhật cấu hình thông báo thành công.');
    }

    /**
     * Danh sách thông báo gần đây của người dùng hiện tại
     *
     * Trả về tối đa 20 thông báo gần nhất cùng số lượng chưa đọc.
     *
     * @response 200 {"success": true, "data": [{"id": "uuid", "title": "Thông báo", "subtitle": "Nội dung ngắn", "icon": "tabler-bell", "color": "primary", "time": "2 minutes ago", "isSeen": false}], "unread_count": 3}
     */
    public function index(Request $request)
    {
        $result = $this->userNotificationService->listNotifications($request->user());

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'unread_count' => $result['unread_count'],
        ]);
    }

    /**
     * Đánh dấu nhiều thông báo là đã đọc
     *
     * @bodyParam ids array required Danh sách ID notification. Example: ["uuid-1", "uuid-2"]
     * @response 200 {"success": true}
     */
    public function markRead(MarkUserNotificationsRequest $request)
    {
        $this->userNotificationService->markRead($request->user(), $request->validated('ids'));

        return $this->success();
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     *
     * @response 200 {"success": true}
     */
    public function markAllRead(Request $request)
    {
        $this->userNotificationService->markAllRead($request->user());

        return $this->success();
    }

    /**
     * Đánh dấu nhiều thông báo là chưa đọc
     *
     * @bodyParam ids array required Danh sách ID notification. Example: ["uuid-1", "uuid-2"]
     * @response 200 {"success": true}
     */
    public function markUnread(MarkUserNotificationsRequest $request)
    {
        $this->userNotificationService->markUnread($request->user(), $request->validated('ids'));

        return $this->success();
    }

    /**
     * Xóa một thông báo của người dùng hiện tại
     *
     * @urlParam id string required ID notification. Example: 3c55ce52-1d4b-4a7d-93bf-bd44c4bc0f09
     * @response 200 {"success": true}
     */
    public function destroy(Request $request, string $id)
    {
        $this->userNotificationService->destroy($request->user(), $id);

        return $this->success();
    }
}

