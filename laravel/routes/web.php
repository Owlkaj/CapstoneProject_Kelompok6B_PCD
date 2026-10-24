<?php

use App\Helpers\DiseaseCatalog;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Deteksi
|--------------------------------------------------------------------------
*/
Route::get('/', [DetectionController::class, 'index'])->name('home');
Route::get('/detect', [DetectionController::class, 'index'])->name('detect.index');
Route::post('/detect', [DetectionController::class, 'predict'])->name('detect.predict');

/*
|--------------------------------------------------------------------------
| Riwayat Deteksi (tampilan web dan JSON API)
|--------------------------------------------------------------------------
*/
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
Route::get('/api/history', [HistoryController::class, 'index'])->name('api.history.index');
Route::get('/history/{id}', [HistoryController::class, 'show'])->name('history.show');
Route::delete('/history/{id}', [HistoryController::class, 'destroy'])->name('history.destroy');

/*
|--------------------------------------------------------------------------
| Tentang Sistem
|--------------------------------------------------------------------------
*/
Route::get('/about', function () {
    return view('about.index', [
        'diseases' => DiseaseCatalog::all(),
    ]);
})->name('about');
