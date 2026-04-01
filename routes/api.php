<?php

use App\Modules\Auth\AuthController;
use App\Modules\Core\OrganizationController;
use App\Modules\Core\SettingController;
use App\Modules\Document\DocumentFieldController;
use App\Modules\Document\DocumentSignerController;
use App\Modules\Document\DocumentTypeController;
use App\Modules\Document\IssuingAgencyController;
use App\Modules\Document\IssuingLevelController;
use App\Modules\Post\PostCategoryController;
use Illuminate\Support\Facades\Route;

// Auth module - public routes
Route::prefix('auth')->middleware('log.activity')->group(function () {
    require base_path('app/Modules/Auth/Routes/auth.php');
});

// Public configuration
Route::get('/settings/public', [SettingController::class, 'public'])->middleware('log.activity');
Route::get('/document-signers/public', [DocumentSignerController::class, 'public'])->middleware('log.activity');
Route::get('/document-signers/public-options', [DocumentSignerController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/document-fields/public', [DocumentFieldController::class, 'public'])->middleware('log.activity');
Route::get('/document-fields/public-options', [DocumentFieldController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/document-types/public', [DocumentTypeController::class, 'public'])->middleware('log.activity');
Route::get('/document-types/public-options', [DocumentTypeController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/issuing-levels/public', [IssuingLevelController::class, 'public'])->middleware('log.activity');
Route::get('/issuing-levels/public-options', [IssuingLevelController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/issuing-agencies/public', [IssuingAgencyController::class, 'public'])->middleware('log.activity');
Route::get('/issuing-agencies/public-options', [IssuingAgencyController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/post-categories/public', [PostCategoryController::class, 'public'])->middleware('log.activity');
Route::get('/post-categories/public-options', [PostCategoryController::class, 'publicOptions'])->middleware('log.activity');
Route::get('/organizations/public', [OrganizationController::class, 'public'])->middleware('log.activity');
Route::get('/organizations/public-options', [OrganizationController::class, 'publicOptions'])->middleware('log.activity');

// Authenticated routes
Route::middleware(['auth:sanctum', 'set.permissions.team', 'log.activity'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/change-password', [AuthController::class, 'changePassword']);
    Route::get('/user/profile', [AuthController::class, 'getProfile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    Route::prefix('user')->group(function () {
        require base_path('app/Modules/Core/Routes/user_notification.php');
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

    Route::prefix('meetings')->group(function () {
        require base_path('app/Modules/Meeting/Routes/meeting.php');
    });

    Route::prefix('my-meetings')->group(function () {
        require base_path('app/Modules/Meeting/Routes/my_meeting.php');
    });

    Route::prefix('settings')->group(function () {
        require base_path('app/Modules/Core/Routes/setting.php');
    });
});
