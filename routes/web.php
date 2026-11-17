<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CsvPreviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoInventoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PersistentDemoController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/imports/preview', [CsvPreviewController::class, 'index'])->name('imports.index');
    Route::post('/imports/preview', [CsvPreviewController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/commit', [CsvPreviewController::class, 'commit'])->name('imports.commit');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::patch('/dashboard/threshold', [DashboardController::class, 'threshold'])->name('dashboard.threshold');
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/demo/database', [PersistentDemoController::class, 'index'])->name('demo.persistent');
    Route::post('/demo/database/change', [PersistentDemoController::class, 'change'])->name('demo.persistent.change');
    Route::post('/demo/database/reset', [PersistentDemoController::class, 'reset'])->name('demo.persistent.reset');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::patch('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::patch('/settings/workspace', [SettingsController::class, 'workspaceName'])->name('settings.workspace');
    Route::patch('/settings/single-sku', [SettingsController::class, 'singleSku'])->name('settings.single-sku');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/receive', [InventoryController::class, 'receive'])->name('inventory.receive');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::post('/catalog', [CatalogController::class, 'store'])->name('catalog.store');
});

// Demo endpoints verify the environment on every request, including with route caching.
Route::get('/demo', [DemoInventoryController::class, 'index'])->name('demo.index');
Route::post('/demo/receive', [DemoInventoryController::class, 'receive'])->name('demo.receive');
Route::post('/demo/adjust', [DemoInventoryController::class, 'adjust'])->name('demo.adjust');
Route::post('/demo/reset', [DemoInventoryController::class, 'reset'])->name('demo.reset');
