<?php

use App\Modules\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Auth module - public routes (đăng nhập, quên mật khẩu, đặt lại mật khẩu)
Route::prefix('auth')->middleware('log.activity')->group(function () {
    require base_path('app/Modules/Auth/Routes/auth.php');
});

// Cấu hình công khai - không cần xác thực
Route::get('/settings/public', [\App\Modules\Core\SettingController::class, 'public'])->middleware('log.activity');
Route::get('/document-signers/public', [\App\Modules\Document\DocumentSignerController::class, 'public'])->middleware('log.activity');
Route::get('/document-signers/public-options', [\App\Modules\Document\DocumentSignerController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/document-fields/public', [\App\Modules\Document\DocumentFieldController::class, 'public'])->middleware('log.activity');
Route::get('/document-fields/public-options', [\App\Modules\Document\DocumentFieldController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/document-types/public', [\App\Modules\Document\DocumentTypeController::class, 'public'])->middleware('log.activity');
Route::get('/document-types/public-options', [\App\Modules\Document\DocumentTypeController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/issuing-levels/public', [\App\Modules\Document\IssuingLevelController::class, 'public'])->middleware('log.activity');
Route::get('/issuing-levels/public-options', [\App\Modules\Document\IssuingLevelController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/issuing-agencies/public', [\App\Modules\Document\IssuingAgencyController::class, 'public'])->middleware('log.activity');
Route::get('/issuing-agencies/public-options', [\App\Modules\Document\IssuingAgencyController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/post-categories/public', [\App\Modules\Post\PostCategoryController::class, 'public'])->middleware('log.activity');
Route::get('/post-categories/public-options', [\App\Modules\Post\PostCategoryController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/organizations/public', [\App\Modules\Core\OrganizationController::class, 'public'])->middleware('log.activity');
Route::get('/organizations/public-options', [\App\Modules\Core\OrganizationController::class, 'publicOptions'])->middleware('log.activity');

// Route yêu cầu đăng nhập (Bearer token) và đặt ngữ cảnh team cho Spatie Permission
Route::middleware(['auth:sanctum', 'set.permissions.team', 'log.activity'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/change-password', [AuthController::class, 'changePassword']);
    Route::get('/user/profile', [AuthController::class, 'getProfile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Notification preferences
    Route::get('/user/notification-preferences', function (\Illuminate\Http\Request $request) {
        $pref = $request->user()->userPreference;

        return response()->json([
            'success' => true,
            'data' => [
                'notify_email' => $pref?->notify_email ?? true,
                'notify_system' => $pref?->notify_system ?? true,
                'notify_meeting_reminder' => $pref?->notify_meeting_reminder ?? true,
                'notify_vote' => $pref?->notify_vote ?? true,
                'notify_document' => $pref?->notify_document ?? false,
            ],
        ]);
    });

    Route::put('/user/notification-preferences', function (\Illuminate\Http\Request $request) {
        $data = $request->validate([
            'notify_email' => 'boolean',
            'notify_system' => 'boolean',
            'notify_meeting_reminder' => 'boolean',
            'notify_vote' => 'boolean',
            'notify_document' => 'boolean',
        ]);

        $request->user()->userPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data,
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình thông báo thành công.',
        ]);
    });

    // Notifications (Laravel Database Notifications)
    Route::get('/user/notifications', function (\Illuminate\Http\Request $request) {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Thông báo',
                'subtitle' => $n->data['subtitle'] ?? $n->data['body'] ?? '',
                'icon' => $n->data['icon'] ?? 'tabler-bell',
                'color' => $n->data['color'] ?? 'primary',
                'time' => $n->created_at->diffForHumans(),
                'isSeen' => $n->read_at !== null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    });

    Route::post('/user/notifications/mark-read', function (\Illuminate\Http\Request $request) {
        $ids = $request->validate(['ids' => 'required|array'])['ids'];

        $request->user()
            ->unreadNotifications()
            ->whereIn('id', $ids)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    });

    Route::post('/user/notifications/mark-all-read', function (\Illuminate\Http\Request $request) {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    });

    Route::post('/user/notifications/mark-unread', function (\Illuminate\Http\Request $request) {
        $ids = $request->validate(['ids' => 'required|array'])['ids'];

        $request->user()
            ->notifications()
            ->whereIn('id', $ids)
            ->update(['read_at' => null]);

        return response()->json(['success' => true]);
    });

    Route::delete('/user/notifications/{id}', function (\Illuminate\Http\Request $request, string $id) {
        $request->user()->notifications()->where('id', $id)->delete();

        return response()->json(['success' => true]);
    });

    Route::prefix('users')->group(function () {
        require base_path('app/Modules/Core/Routes/user.php');
    });
    Route::prefix('posts')->group(function () {
        require base_path('app/Modules/Post/Routes/post.php');
    });
    Route::prefix('post-categories')->group(function () {
        require base_path('app/Modules/Post/Routes/post_category.php');
    });
    Route::prefix('permissions')->group(function () {
        require base_path('app/Modules/Core/Routes/permission.php');
    });
    Route::prefix('roles')->group(function () {
        require base_path('app/Modules/Core/Routes/role.php');
    });
    Route::prefix('organizations')->group(function () {
        require base_path('app/Modules/Core/Routes/organization.php');
    });
    Route::prefix('log-activities')->group(function () {
        require base_path('app/Modules/Core/Routes/log_activity.php');
    });
    Route::prefix('documents')->group(function () {
        require base_path('app/Modules/Document/Routes/document.php');
    });
    Route::prefix('document-types')->group(function () {
        require base_path('app/Modules/Document/Routes/document_type.php');
    });
    Route::prefix('issuing-agencies')->group(function () {
        require base_path('app/Modules/Document/Routes/issuing_agency.php');
    });
    Route::prefix('issuing-levels')->group(function () {
        require base_path('app/Modules/Document/Routes/issuing_level.php');
    });
    Route::prefix('document-signers')->group(function () {
        require base_path('app/Modules/Document/Routes/document_signer.php');
    });
    Route::prefix('document-fields')->group(function () {
        require base_path('app/Modules/Document/Routes/document_field.php');
    });
    // Meeting - Cuộc họp không giấy
    Route::prefix('meetings')->group(function () {
        require base_path('app/Modules/Meeting/Routes/meeting.php');
    });
    // My Meetings - Phân hệ Đại biểu (lịch họp của tôi)
    Route::prefix('my-meetings')->group(function () {
        require base_path('app/Modules/Meeting/Routes/my_meeting.php');
    });

    Route::prefix('settings')->group(function () {
        require base_path('app/Modules/Core/Routes/setting.php');
    });
});
