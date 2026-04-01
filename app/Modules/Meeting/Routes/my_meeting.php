<?php

use App\Modules\Meeting\MyMeetingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| My Meeting Routes — Phân hệ Đại biểu
|--------------------------------------------------------------------------
| Prefix: /api/my-meetings
| Dùng cho đại biểu xem danh sách cuộc họp được mời, chi tiết, và thông tin tham gia.
*/

Route::get('/', [MyMeetingController::class, 'index']);
Route::get('/{meeting}', [MyMeetingController::class, 'show']);
Route::get('/{meeting}/my-info', [MyMeetingController::class, 'myInfo']);
