<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/microsoft/redirect', [AuthController::class, 'microsoftRedirect'])->name('microsoft.redirect');
Route::get('/auth/microsoft/callback', [AuthController::class, 'microsoftCallback'])->name('microsoft.callback');

Route::view('/admin', 'admin')->middleware('auth')->name('admin');

Route::redirect('/', '/login');
