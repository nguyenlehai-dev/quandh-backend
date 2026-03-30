<?php

use App\Modules\Core\UserNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/notification-preferences', [UserNotificationController::class, 'preferences']);
Route::put('/notification-preferences', [UserNotificationController::class, 'updatePreferences']);

Route::get('/notifications', [UserNotificationController::class, 'index']);
Route::post('/notifications/mark-read', [UserNotificationController::class, 'markRead']);
Route::post('/notifications/mark-all-read', [UserNotificationController::class, 'markAllRead']);
Route::post('/notifications/mark-unread', [UserNotificationController::class, 'markUnread']);
Route::delete('/notifications/{id}', [UserNotificationController::class, 'destroy']);
