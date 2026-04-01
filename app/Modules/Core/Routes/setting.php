<?php

use App\Modules\Core\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SettingController::class, 'index'])->middleware('permission:settings.index,api');
Route::get('/{key}', [SettingController::class, 'show'])->middleware('permission:settings.show,api');
Route::match(['put', 'patch'], '/', [SettingController::class, 'update'])->middleware('permission:settings.update,api');
