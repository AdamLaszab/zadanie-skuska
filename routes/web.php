<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;

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

});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
