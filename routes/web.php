<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ExportController;
use App\Livewire\NdcSearch;

// Public routes
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    // NDC Lookup routes
    Route::get('/dashboard', NdcSearch::class)->name('dashboard');
    Route::get('/products/search', NdcSearch::class)->name('products.search');
    Route::get('/export-csv', [ExportController::class, 'exportCsv'])->name('export.csv');

    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/export', [ProductController::class, 'exportCSV'])->name('products.export');
        Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
});

// Authentication routes (Breeze)
require __DIR__.'/auth.php';