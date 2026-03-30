<?php

use App\Modules\Core\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/template', [UserController::class, 'template'])->middleware('permission:users.import,api');
Route::get('/export', [UserController::class, 'export'])->middleware('permission:users.export,api');
Route::post('/import', [UserController::class, 'import'])->middleware('permission:users.import,api');
Route::post('/bulk-delete', [UserController::class, 'bulkDestroy'])->middleware('permission:users.bulkDestroy,api');
Route::patch('/bulk-status', [UserController::class, 'bulkUpdateStatus'])->middleware('permission:users.bulkUpdateStatus,api');
Route::get('/stats', [UserController::class, 'stats'])->middleware('permission:users.stats,api');
Route::get('/', [UserController::class, 'index'])->middleware('permission:users.index,api');
Route::get('/{user}', [UserController::class, 'show'])->middleware('permission:users.show,api');
Route::post('/', [UserController::class, 'store'])->middleware('permission:users.store,api');
Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:users.update,api');
Route::patch('/{user}', [UserController::class, 'update'])->middleware('permission:users.update,api');
Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('permission:users.destroy,api');
Route::patch('/{user}/status', [UserController::class, 'changeStatus'])->middleware('permission:users.changeStatus,api');
