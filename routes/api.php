<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle.login:5,1');

Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
    ->name('sanctum.csrf-cookie');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/user/password', [AuthController::class, 'changePassword'])->name('api.user.password');
    
    Route::middleware('throttle.api:60,1')->group(function () {
        Route::apiResource('tasks', TaskController::class);
        Route::get('/users', [UserController::class, 'index'])
            ->middleware('can:view-any-user');

    });
});