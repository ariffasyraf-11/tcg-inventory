<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CsvPreviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoInventoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PersistentDemoController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceSwitchController;
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
    Route::get('/workspaces/switch', [WorkspaceSwitchController::class, 'index'])->name('workspaces.switch.index');
    Route::post('/workspaces/switch/{workspace}', [WorkspaceSwitchController::class, 'update'])->name('workspaces.switch.update');
    Route::get('/workspace/invitations/inbox', [WorkspaceInvitationController::class, 'inbox'])->name('workspace-invitations.inbox');
    Route::post('/workspace/invitations/inbox/{invitation}/accept', [WorkspaceInvitationController::class, 'acceptFromInbox'])->name('workspace-invitations.inbox.accept');
    Route::post('/workspace/invitations/inbox/{invitation}/decline', [WorkspaceInvitationController::class, 'declineFromInbox'])->name('workspace-invitations.inbox.decline');
    Route::get('/workspace/invitations', [WorkspaceInvitationController::class, 'index'])->name('workspace-invitations.index');
    Route::post('/workspace/invitations', [WorkspaceInvitationController::class, 'store'])->middleware('throttle:10,1')->name('workspace-invitations.store');
    Route::get('/workspace/invitations/accept/{token}', [WorkspaceInvitationController::class, 'show'])->name('workspace-invitations.show');
    Route::post('/workspace/invitations/accept/{token}', [WorkspaceInvitationController::class, 'accept'])->name('workspace-invitations.accept');
    Route::delete('/workspace/invitations/{invitation}', [WorkspaceInvitationController::class, 'destroy'])->name('workspace-invitations.destroy');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::patch('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::patch('/settings/workspace', [SettingsController::class, 'workspaceName'])->name('settings.workspace');
    Route::patch('/settings/catalog-edit-role', [SettingsController::class, 'catalogEditRole'])->name('settings.catalog-edit-role');
    Route::patch('/settings/single-sku', [SettingsController::class, 'singleSku'])->name('settings.single-sku');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/{item}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('/inventory/receive', [InventoryController::class, 'receive'])->name('inventory.receive');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{product}/image', [ProductImageController::class, 'show'])->name('catalog.image.show');
    Route::post('/catalog/{product}/image', [ProductImageController::class, 'store'])->name('catalog.image.store');
    Route::delete('/catalog/{product}/image', [ProductImageController::class, 'destroy'])->name('catalog.image.destroy');
    Route::get('/catalog/{product}/edit', [CatalogController::class, 'edit'])->name('catalog.edit');
    Route::patch('/catalog/{product}', [CatalogController::class, 'update'])->name('catalog.update');
    Route::post('/catalog', [CatalogController::class, 'store'])->name('catalog.store');
});

// Demo endpoints verify the environment on every request, including with route caching.
Route::get('/demo', [DemoInventoryController::class, 'index'])->name('demo.index');
Route::post('/demo/receive', [DemoInventoryController::class, 'receive'])->name('demo.receive');
Route::post('/demo/adjust', [DemoInventoryController::class, 'adjust'])->name('demo.adjust');
Route::post('/demo/reset', [DemoInventoryController::class, 'reset'])->name('demo.reset');
