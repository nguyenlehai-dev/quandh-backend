<?php

use App\Modules\Post\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [PostController::class, 'export'])->middleware('permission:posts.export,api');
Route::post('/import', [PostController::class, 'import'])->middleware('permission:posts.import,api');
Route::post('/bulk-delete', [PostController::class, 'bulkDestroy'])->middleware('permission:posts.bulkDestroy,api');
Route::patch('/bulk-status', [PostController::class, 'bulkUpdateStatus'])->middleware('permission:posts.bulkUpdateStatus,api');
Route::get('/stats', [PostController::class, 'stats'])->middleware('permission:posts.stats,api');
Route::get('/', [PostController::class, 'index'])->middleware('permission:posts.index,api');
Route::get('/{post}', [PostController::class, 'show'])->middleware('permission:posts.show,api');
Route::post('/{post}/view', [PostController::class, 'incrementView'])->middleware('permission:posts.incrementView,api');
Route::post('/', [PostController::class, 'store'])->middleware('permission:posts.store,api');
Route::put('/{post}', [PostController::class, 'update'])->middleware('permission:posts.update,api');
Route::patch('/{post}', [PostController::class, 'update'])->middleware('permission:posts.update,api');
Route::delete('/{post}', [PostController::class, 'destroy'])->middleware('permission:posts.destroy,api');
Route::patch('/{post}/status', [PostController::class, 'changeStatus'])->middleware('permission:posts.changeStatus,api');
