<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DemoInventoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::post('/catalog', [CatalogController::class, 'store'])->name('catalog.store');
});

// Demo endpoints verify the environment on every request, including with route caching.
Route::get('/demo', [DemoInventoryController::class, 'index'])->name('demo.index');
Route::post('/demo/receive', [DemoInventoryController::class, 'receive'])->name('demo.receive');
Route::post('/demo/adjust', [DemoInventoryController::class, 'adjust'])->name('demo.adjust');
Route::post('/demo/reset', [DemoInventoryController::class, 'reset'])->name('demo.reset');
