<?php

use App\Modules\Core\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/template', [RoleController::class, 'template'])->middleware('permission:roles.import,api');
Route::get('/export', [RoleController::class, 'export'])->middleware('permission:roles.export,api');
Route::post('/import', [RoleController::class, 'import'])->middleware('permission:roles.import,api');
Route::post('/bulk-delete', [RoleController::class, 'bulkDestroy'])->middleware('permission:roles.bulkDestroy,api');
Route::get('/stats', [RoleController::class, 'stats'])->middleware('permission:roles.stats,api');
Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.index,api');
Route::get('/{role}', [RoleController::class, 'show'])->middleware('permission:roles.show,api');
Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.store,api');
Route::put('/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update,api');
Route::patch('/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update,api');
Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.destroy,api');
