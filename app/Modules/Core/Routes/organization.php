<?php

use App\Modules\Core\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::get('/template', [OrganizationController::class, 'template'])->middleware('permission:organizations.import,api');
Route::get('/export', [OrganizationController::class, 'export'])->middleware('permission:organizations.export,api');
Route::post('/import', [OrganizationController::class, 'import'])->middleware('permission:organizations.import,api');
Route::post('/bulk-delete', [OrganizationController::class, 'bulkDestroy'])->middleware('permission:organizations.bulkDestroy,api');
Route::patch('/bulk-status', [OrganizationController::class, 'bulkUpdateStatus'])->middleware('permission:organizations.bulkUpdateStatus,api');
Route::get('/stats', [OrganizationController::class, 'stats'])->middleware('permission:organizations.stats,api');
Route::get('/tree', [OrganizationController::class, 'tree'])->middleware('permission:organizations.tree,api');
Route::get('/', [OrganizationController::class, 'index'])->middleware('permission:organizations.index,api');
Route::get('/{organization}', [OrganizationController::class, 'show'])->middleware('permission:organizations.show,api');
Route::post('/', [OrganizationController::class, 'store'])->middleware('permission:organizations.store,api');
Route::put('/{organization}', [OrganizationController::class, 'update'])->middleware('permission:organizations.update,api');
Route::patch('/{organization}', [OrganizationController::class, 'update'])->middleware('permission:organizations.update,api');
Route::delete('/{organization}', [OrganizationController::class, 'destroy'])->middleware('permission:organizations.destroy,api');
Route::patch('/{organization}/status', [OrganizationController::class, 'changeStatus'])->middleware('permission:organizations.changeStatus,api');
