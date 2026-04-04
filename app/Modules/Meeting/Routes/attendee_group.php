<?php

use App\Modules\Meeting\AttendeeGroupController;
use App\Modules\Meeting\AttendeeGroupMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/public', [AttendeeGroupController::class, 'public']);
Route::get('/public-options', [AttendeeGroupController::class, 'publicOptions']);
Route::get('/export', [AttendeeGroupController::class, 'export'])->middleware('permission:attendee-groups.index,web');
Route::post('/import', [AttendeeGroupController::class, 'import'])->middleware('permission:attendee-groups.store,web');
Route::post('/bulk-delete', [AttendeeGroupController::class, 'bulkDestroy'])->middleware('permission:attendee-groups.destroy,web');
Route::patch('/bulk-status', [AttendeeGroupController::class, 'bulkUpdateStatus'])->middleware('permission:attendee-groups.update,web');
Route::get('/stats', [AttendeeGroupController::class, 'stats'])->middleware('permission:attendee-groups.index,web');
Route::get('/', [AttendeeGroupController::class, 'index'])->middleware('permission:attendee-groups.index,web');
Route::get('/{attendeeGroup}', [AttendeeGroupController::class, 'show'])->middleware('permission:attendee-groups.show,web');
Route::post('/', [AttendeeGroupController::class, 'store'])->middleware('permission:attendee-groups.store,web');
Route::put('/{attendeeGroup}', [AttendeeGroupController::class, 'update'])->middleware('permission:attendee-groups.update,web');
Route::patch('/{attendeeGroup}', [AttendeeGroupController::class, 'update'])->middleware('permission:attendee-groups.update,web');
Route::delete('/{attendeeGroup}', [AttendeeGroupController::class, 'destroy'])->middleware('permission:attendee-groups.destroy,web');
Route::patch('/{attendeeGroup}/status', [AttendeeGroupController::class, 'changeStatus'])->middleware('permission:attendee-groups.update,web');
Route::get('/{attendeeGroup}/members', [AttendeeGroupMemberController::class, 'index'])->middleware('permission:attendee-group-members.index,web');
Route::post('/{attendeeGroup}/members', [AttendeeGroupMemberController::class, 'store'])->middleware('permission:attendee-group-members.store,web');
Route::put('/{attendeeGroup}/members/{member}', [AttendeeGroupMemberController::class, 'update'])->middleware('permission:attendee-group-members.update,web');
Route::delete('/{attendeeGroup}/members/{member}', [AttendeeGroupMemberController::class, 'destroy'])->middleware('permission:attendee-group-members.destroy,web');
