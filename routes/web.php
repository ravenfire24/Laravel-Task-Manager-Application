<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskOrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tasks');
Route::patch('/tasks/order', TaskOrderController::class)->name('tasks.order');
Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
Route::resource('tasks', TaskController::class);
