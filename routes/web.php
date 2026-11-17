<?php

use App\Http\Controllers\DemoInventoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/demo', [DemoInventoryController::class, 'index'])->name('demo.index');
Route::post('/demo/receive', [DemoInventoryController::class, 'receive'])->name('demo.receive');
Route::post('/demo/adjust', [DemoInventoryController::class, 'adjust'])->name('demo.adjust');
Route::post('/demo/reset', [DemoInventoryController::class, 'reset'])->name('demo.reset');
