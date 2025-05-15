<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PdfInertiaController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\FileTestController;

// Route::get('/', function () {
//     return Inertia::render('Welcome');
// })->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function() {
//     Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');
//     Route::get('/logs/export', [ActivityLogController::class, 'export'])->name('logs.export');
//     Route::delete('/logs', [ActivityLogController::class, 'clear'])->name('logs.clear');
// });

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'storeLogin']);
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'storeRegister']);

Route::middleware('auth')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    Route::get('/tools/pdf/merge', [PdfInertiaController::class, 'showMergeForm'])->name('pdf.tool.merge.show');
    Route::post('/tools/pdf/merge', [PdfInertiaController::class, 'processMerge'])->name('pdf.tool.merge.process');

    // Routa na sťahovanie dočasných súborov
    Route::get('/download/temporary/{token}', [FileDownloadController::class, 'downloadTemporaryFile'])->name('file.download.temporary');

    Route::get('/file-test', [FileTestController::class, 'showForm'])->name('file.test');
Route::post('/file-test', [FileTestController::class, 'processUpload'])->name('file.test.upload');

});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
