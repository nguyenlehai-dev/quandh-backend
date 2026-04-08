<?php

use App\Modules\Meeting\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/stats', [CatalogController::class, 'stats'])->middleware("permission:{$resource}.stats,web")->defaults('resource', $resource);
Route::get('/', [CatalogController::class, 'index'])->middleware("permission:{$resource}.index,web")->defaults('resource', $resource);
Route::post('/bulk-delete', [CatalogController::class, 'bulkDestroy'])->middleware("permission:{$resource}.bulkDestroy,web")->defaults('resource', $resource);
Route::patch('/bulk-status', [CatalogController::class, 'bulkUpdateStatus'])->middleware("permission:{$resource}.bulkUpdateStatus,web")->defaults('resource', $resource);
Route::post('/', [CatalogController::class, 'store'])->middleware("permission:{$resource}.store,web")->defaults('resource', $resource);
Route::get('/{id}', [CatalogController::class, 'show'])->middleware("permission:{$resource}.show,web")->defaults('resource', $resource)->whereNumber('id');
Route::put('/{id}', [CatalogController::class, 'update'])->middleware("permission:{$resource}.update,web")->defaults('resource', $resource)->whereNumber('id');
Route::patch('/{id}', [CatalogController::class, 'update'])->middleware("permission:{$resource}.update,web")->defaults('resource', $resource)->whereNumber('id');
Route::delete('/{id}', [CatalogController::class, 'destroy'])->middleware("permission:{$resource}.destroy,web")->defaults('resource', $resource)->whereNumber('id');
Route::patch('/{id}/status', [CatalogController::class, 'changeStatus'])->middleware("permission:{$resource}.changeStatus,web")->defaults('resource', $resource)->whereNumber('id');
