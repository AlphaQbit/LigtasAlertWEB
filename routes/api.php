<?php

use App\Http\Controllers\Api\AlertController as MobileAlertController;
use App\Http\Controllers\Api\AuthController as MobileAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rescuee app. Sending requires authentication so every alert is tied to a
// rescuee account. This ensures ownership for status reading and prevents abuse.
Route::post('/alerts', [MobileAlertController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:alerts']);
Route::get('/alerts/{alert}', [MobileAlertController::class, 'show'])
    ->middleware('auth:sanctum')
    ->whereNumber('alert');

// Rescuee app auth. Bearer tokens (Sanctum), not session cookies, so the
// browser dev server and a real device use exactly the same endpoints.
Route::post('/auth/register', [MobileAuthController::class, 'register'])
    ->middleware('throttle:register');
Route::post('/auth/login', [MobileAuthController::class, 'login'])
    ->middleware('throttle:login');
Route::get('/auth/microsoft/start', [MobileAuthController::class, 'microsoftStart']);
Route::get('/auth/microsoft/callback', [MobileAuthController::class, 'microsoftCallback']);
Route::get('/auth/microsoft/finish', [MobileAuthController::class, 'microsoftFinish']);
Route::get('/auth/me', [MobileAuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
