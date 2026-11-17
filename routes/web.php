<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/produk', [CatalogController::class, 'index']);
Route::get('/produk/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/admin/login', [AuthController::class, 'form'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1'])->name('admin.login.submit');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active.admin'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('password', [AuthController::class, 'password'])->name('password');
    Route::get('export', ExportController::class)->middleware('throttle:10,1')->name('export');
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('products/{product}/history', [ProductController::class, 'history'])->name('products.history');
    Route::patch('products/{product}/stock', [ProductController::class, 'stock'])->name('products.stock');
    Route::post('products/{product}/images', [ImageController::class, 'store'])->middleware('throttle:20,1')->name('products.images.store');
    Route::patch('products/{product}/images/{image}/cover', [ImageController::class, 'cover'])->name('products.images.cover');
    Route::delete('products/{product}/images/{image}', [ImageController::class, 'destroy'])->name('products.images.destroy');
    Route::resource('products', ProductController::class)->except(['show']);
});
