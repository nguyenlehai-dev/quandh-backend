<?php

use App\Modules\Core\PermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/template', [PermissionController::class, 'template'])->middleware('permission:permissions.import,api');
Route::get('/export', [PermissionController::class, 'export'])->middleware('permission:permissions.export,api');
Route::post('/import', [PermissionController::class, 'import'])->middleware('permission:permissions.import,api');
Route::post('/bulk-delete', [PermissionController::class, 'bulkDestroy'])->middleware('permission:permissions.bulkDestroy,api');
Route::get('/stats', [PermissionController::class, 'stats'])->middleware('permission:permissions.stats,api');
Route::get('/tree', [PermissionController::class, 'tree'])->middleware('permission:permissions.tree,api');
Route::get('/', [PermissionController::class, 'index'])->middleware('permission:permissions.index,api');
Route::get('/{permission}', [PermissionController::class, 'show'])->middleware('permission:permissions.show,api');
Route::post('/', [PermissionController::class, 'store'])->middleware('permission:permissions.store,api');
Route::put('/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update,api');
Route::patch('/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update,api');
Route::delete('/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.destroy,api');
