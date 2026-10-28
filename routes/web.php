<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// UI scaffold only. Add auth middleware before exposing operational data.
Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
