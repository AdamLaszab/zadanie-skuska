<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfApiController;
use App\Http\Controllers\ApiKeyController;


Route::prefix('pdf')->name('pdf.')->group(function () {
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
});

Route::get('/api-key', [ApiKeyController::class, 'newApiKeyApi'])->name('newApiKeyApi');