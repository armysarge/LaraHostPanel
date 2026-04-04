<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/start', [ProjectController::class, 'start'])->name('projects.start');
    Route::post('/projects/{project}/stop', [ProjectController::class, 'stop'])->name('projects.stop');
    Route::get('/projects/{project}/env', [ProjectController::class, 'envEdit'])->name('projects.env.edit');
    Route::put('/projects/{project}/env', [ProjectController::class, 'envUpdate'])->name('projects.env.update');
});
