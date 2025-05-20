<?php

use App\Http\Controllers\Api\ActivityLogApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\FileDownloadApiController;
use App\Http\Controllers\Api\ManualApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfApiController;
use App\Http\Controllers\ApiKeyController;


Route::middleware(['validate.api'])->prefix('pdf')->name('pdf.')->group(function () {
    Route::post('/merge', [PdfApiController::class, 'merge'])->name('merge');
    Route::post('/rotate', [PdfApiController::class, 'rotate'])->name('rotate');
    Route::post('/delete-pages', [PdfApiController::class, 'deletePages'])->name('deletePages');
    Route::post('/extract-pages', [PdfApiController::class, 'extractPages'])->name('extractPages');
    Route::post('/encrypt', [PdfApiController::class, 'encrypt'])->name('encrypt');
    Route::post('/decrypt', [PdfApiController::class, 'decrypt'])->name('decrypt');
    Route::post('/overlay', [PdfApiController::class, 'overlay'])->name('overlay');
    Route::post('/extract-text', [PdfApiController::class, 'extractText'])->name('extractText');
    Route::post('/reverse-pages', [PdfApiController::class, 'reversePages'])->name('reversePages');
    Route::post('/duplicate-pages', [PdfApiController::class, 'duplicatePages'])->name('duplicatePages');
    
    Route::post('/logout', [AuthApiController::class, 'logout']);
    
    Route::get('/users', [UserApiController::class, 'index']);
    Route::put('/users/{user}/role', [UserApiController::class, 'updateRole']);
    Route::get('/profile', [UserApiController::class, 'profile']);
    Route::put('/profile', [UserApiController::class, 'update']);
    Route::put('/profile/password', [UserApiController::class, 'updatePassword']);
    
    Route::get('/api-key', [ApiKeyController::class, 'newApiKeyApi'])->name('newApiKeyApi');

    Route::get('/manual', [ManualApiController::class, 'show']);
    Route::get('/manual/pdf', [ManualApiController::class, 'exportPdf']);

    Route::get('/download/{token}', [FileDownloadApiController::class, 'download']);

    Route::get('/logs', [ActivityLogApiController::class, 'index']);
    Route::get('/logs/export', [ActivityLogApiController::class, 'export']);
    Route::delete('/logs', [ActivityLogApiController::class, 'clear']);
});

Route::post('/login', [AuthApiController::class, 'login']);
Route::post('/register', [AuthApiController::class, 'register']);

