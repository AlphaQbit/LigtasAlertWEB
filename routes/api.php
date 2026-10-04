<?php

use App\Http\Controllers\Api\AlertController as MobileAlertController;
use App\Http\Controllers\Api\AuthController as MobileAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rescuee app. Sending stays unauthenticated on purpose: a rescuee in trouble
// must never be blocked from calling for help by a login.
//
// Reading is gated. An alert carries a facility and a room number, so leaving
// this open let anyone who guessed an id read someone else's emergency. The
// app only reaches this route after logging in, so requiring a token costs a
// logged-in rescuee nothing.
Route::post('/alerts', [MobileAlertController::class, 'store']);
Route::get('/alerts/{alert}', [MobileAlertController::class, 'show'])
    ->middleware('auth:sanctum')
    ->whereNumber('alert');

// Rescuee app auth. Bearer tokens (Sanctum), not session cookies, so the
// browser dev server and a real device use exactly the same endpoints.
Route::post('/auth/register', [MobileAuthController::class, 'register']);
Route::post('/auth/login', [MobileAuthController::class, 'login']);
Route::get('/auth/microsoft/start', [MobileAuthController::class, 'microsoftStart']);
Route::get('/auth/microsoft/callback', [MobileAuthController::class, 'microsoftCallback']);
Route::get('/auth/microsoft/finish', [MobileAuthController::class, 'microsoftFinish']);
Route::get('/auth/me', [MobileAuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
