<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ChunkUploadController;
use Illuminate\Support\Facades\Route;

// Protected API routes (session-based auth via web middleware)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files', [FileController::class, 'store']);
    Route::delete('/files/{file}', [FileController::class, 'destroy']);

    // Chunked upload routes for FilePond
    Route::post('/chunks', [ChunkUploadController::class, 'start']);
    Route::patch('/chunks/{uploadId}', [ChunkUploadController::class, 'chunk']);
    Route::match(['HEAD'], '/chunks/{uploadId}', [ChunkUploadController::class, 'progress']);
    Route::delete('/chunks/{uploadId}', [ChunkUploadController::class, 'cancel']);
});
