<?php

use App\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

// Protected API routes (session-based auth via web middleware)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files', [FileController::class, 'store']);
    Route::delete('/files/{file}', [FileController::class, 'destroy']);
});
