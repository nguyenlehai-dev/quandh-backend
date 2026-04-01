<?php

use App\Modules\Post\PostCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [PostCategoryController::class, 'export'])->middleware('permission:post-categories.export,api');
Route::post('/import', [PostCategoryController::class, 'import'])->middleware('permission:post-categories.import,api');
Route::post('/bulk-delete', [PostCategoryController::class, 'bulkDestroy'])->middleware('permission:post-categories.bulkDestroy,api');
Route::patch('/bulk-status', [PostCategoryController::class, 'bulkUpdateStatus'])->middleware('permission:post-categories.bulkUpdateStatus,api');
Route::get('/stats', [PostCategoryController::class, 'stats'])->middleware('permission:post-categories.stats,api');
Route::get('/tree', [PostCategoryController::class, 'tree'])->middleware('permission:post-categories.tree,api');
Route::get('/', [PostCategoryController::class, 'index'])->middleware('permission:post-categories.index,api');
Route::get('/{category}', [PostCategoryController::class, 'show'])->middleware('permission:post-categories.show,api');
Route::post('/', [PostCategoryController::class, 'store'])->middleware('permission:post-categories.store,api');
Route::put('/{category}', [PostCategoryController::class, 'update'])->middleware('permission:post-categories.update,api');
Route::patch('/{category}', [PostCategoryController::class, 'update'])->middleware('permission:post-categories.update,api');
Route::delete('/{category}', [PostCategoryController::class, 'destroy'])->middleware('permission:post-categories.destroy,api');
Route::patch('/{category}/status', [PostCategoryController::class, 'changeStatus'])->middleware('permission:post-categories.changeStatus,api');
