<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PdfInertiaController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\FileTestController;
use App\Http\Controllers\UserController;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'storeLogin']);
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'storeRegister']);

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [UserController::class, 'index'])->name('profile');
    Route::put('/profile', [UserController::class, 'index']);
    Route::post('/api-key/regenerate', [ApiKeyController::class, 'newApiKeyInertia'])->name('api.key.regenerate');
});



Route::middleware(['auth', 'permission:use-pdf-tools'])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    Route::get('/tools/pdf/merge', [PdfInertiaController::class, 'showMergeForm'])->name('pdf.tool.merge.show');
    Route::post('/tools/pdf/merge', [PdfInertiaController::class, 'processMerge'])->name('pdf.tool.merge.process');

    Route::get('/download/temporary/{token}', [FileDownloadController::class, 'downloadTemporaryFile'])->name('file.download.temporary');

    Route::get('/tools/pdf/extract-pages', [PdfInertiaController::class, 'showExtractPagesForm'])->name('pdf.tool.extract_pages.show');
    Route::post('/tools/pdf/extract-pages', [PdfInertiaController::class, 'processExtractPages'])->name('pdf.tool.extract_pages.process');
    Route::get('/tools/pdf/rotate', [PdfInertiaController::class, 'showRotateForm'])->name('pdf.tool.rotate.show');
    Route::post('/tools/pdf/rotate', [PdfInertiaController::class, 'processRotate'])->name('pdf.tool.rotate.process');
    Route::get('/tools/pdf/delete-pages', [PdfInertiaController::class, 'showDeletePagesForm'])->name('pdf.tool.delete_pages.show');
    Route::post('/tools/pdf/delete-pages', [PdfInertiaController::class, 'processDeletePages'])->name('pdf.tool.delete_pages.process');
    Route::get('/tools/pdf/encrypt', [PdfInertiaController::class, 'showEncryptForm'])->name('pdf.tool.encrypt.show');
    Route::post('/tools/pdf/encrypt', [PdfInertiaController::class, 'processEncrypt'])->name('pdf.tool.encrypt.process');
    Route::get('/tools/pdf/decrypt', [PdfInertiaController::class, 'showDecryptForm'])->name('pdf.tool.decrypt.show');
    Route::post('/tools/pdf/decrypt', [PdfInertiaController::class, 'processDecrypt'])->name('pdf.tool.decrypt.process');
    Route::get('/tools/pdf/overlay', [PdfInertiaController::class, 'showOverlayForm'])->name('pdf.tool.overlay.show');
    Route::post('/tools/pdf/overlay', [PdfInertiaController::class, 'processOverlay'])->name('pdf.tool.overlay.process');
    Route::get('/tools/pdf/extract-text', [PdfInertiaController::class, 'showExtractTextForm'])->name('pdf.tool.extract_text.show');
    Route::post('/tools/pdf/extract-text', [PdfInertiaController::class, 'processExtractText'])->name('pdf.tool.extract_text.process');
    Route::get('/tools/pdf/reverse-pages', [PdfInertiaController::class, 'showReversePagesForm'])->name('pdf.tool.reverse_pages.show');
    Route::post('/tools/pdf/reverse-pages', [PdfInertiaController::class, 'processReversePages'])->name('pdf.tool.reverse_pages.process');
    Route::get('/tools/pdf/duplicate-pages', [PdfInertiaController::class, 'showDuplicatePagesForm'])->name('pdf.tool.duplicate_pages.show');
    Route::post('/tools/pdf/duplicate-pages', [PdfInertiaController::class, 'processDuplicatePages'])->name('pdf.tool.duplicate_pages.process');
});

Route::middleware(['auth', 'permission:view-users'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users');
});

Route::middleware(['auth', 'permission:view-any-usage-history'])->prefix('admin')->name('admin.')->group(function () {
     Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

    Route::get('/logs/export', [ActivityLogController::class, 'export'])->name('logs.export');

    Route::delete('/logs/clear', [ActivityLogController::class, 'clear'])->name('logs.clear');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
