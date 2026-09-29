<?php

use Illuminate\Support\Facades\Route;

Route::view('/login', 'auth')->name('login');
Route::view('/admin', 'admin')->name('admin');

Route::redirect('/', '/login');
