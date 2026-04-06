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
Route::get('/meeting-types/public', [\App\Modules\Meeting\MeetingTypeController::class, 'public'])->middleware('log.activity');
Route::get('/meeting-types/public-options', [\App\Modules\Meeting\MeetingTypeController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/meeting-document-types/public', [\App\Modules\Meeting\MeetingDocumentTypeController::class, 'public'])->middleware('log.activity');
Route::get('/meeting-document-types/public-options', [\App\Modules\Meeting\MeetingDocumentTypeController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/meeting-document-fields/public', [\App\Modules\Meeting\MeetingDocumentFieldController::class, 'public'])->middleware('log.activity');
Route::get('/meeting-document-fields/public-options', [\App\Modules\Meeting\MeetingDocumentFieldController::class, 'publicOptions'])->middleware('log.activity');

// Route yêu cầu đăng nhập (Bearer token) và đặt ngữ cảnh team cho Spatie Permission
Route::middleware(['auth:sanctum', 'set.permissions.team', 'log.activity'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);

    // User notifications (stub)
    Route::prefix('user/notifications')->group(function () {
        Route::get('/', [\App\Modules\Auth\UserNotificationController::class, 'index']);
        Route::post('/mark-read', [\App\Modules\Auth\UserNotificationController::class, 'markRead']);
        Route::post('/mark-unread', [\App\Modules\Auth\UserNotificationController::class, 'markUnread']);
        Route::delete('/{id}', [\App\Modules\Auth\UserNotificationController::class, 'destroy']);
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
    Route::prefix('meeting-types')->group(function () {
        require base_path('app/Modules/Meeting/Routes/meeting_type.php');
    });
    Route::prefix('attendee-groups')->group(function () {
        require base_path('app/Modules/Meeting/Routes/attendee_group.php');
    });
    Route::prefix('meeting-document-types')->group(function () {
        require base_path('app/Modules/Meeting/Routes/meeting_document_type.php');
    });
    Route::prefix('meeting-document-fields')->group(function () {
        require base_path('app/Modules/Meeting/Routes/meeting_document_field.php');
    });
    Route::prefix('meetings')->group(function () {
        require base_path('app/Modules/Meeting/Routes/meeting.php');
    });
    Route::prefix('admin/meetings')->group(function () {
        require base_path('app/Modules/Meeting/Routes/admin/meeting.php');
    });
    Route::prefix('participant')->group(function () {
        require base_path('app/Modules/Meeting/Routes/participant/meeting.php');
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
    Route::prefix('settings')->group(function () {
        require base_path('app/Modules/Core/Routes/setting.php');
    });
});
