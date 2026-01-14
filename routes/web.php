<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;

// Public download routes
Route::get('/d/{token}', [DownloadController::class, 'show'])->name('download.show');
Route::get('/d/{token}/file', [DownloadController::class, 'download'])->name('download.file');

// Auth routes
Route::middleware('guest')->group(function () {
  Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
  Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// SPA routes - catch all for Vue Router
Route::middleware('auth')->group(function () {
    Route::get('/{any?}', function () {
        return view('spa');
    })->where('any', '^(?!api|d/).*$')->name('spa');
});
