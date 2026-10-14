<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskOrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tasks');
Route::resource('tasks', TaskController::class)
    ->only(['index', 'create', 'show', 'edit'])
    ->middleware('throttle:task-reads');

Route::middleware('throttle:task-writes')->group(function () {
    Route::patch('/tasks/order', TaskOrderController::class)->name('tasks.order');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::resource('tasks', TaskController::class)->only(['store', 'update', 'destroy']);
});
