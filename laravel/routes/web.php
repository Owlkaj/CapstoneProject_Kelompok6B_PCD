<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DetectionController;
use App\Http\Controllers\HistoryController;

// Main Detection Routes
Route::get('/', [DetectionController::class, 'index'])->name('home');
Route::get('/detect', [DetectionController::class, 'index'])->name('detect.index');
Route::post('/detect', [DetectionController::class, 'predict'])->name('detect.predict');

// History Routes (Web view & JSON API)
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
Route::get('/api/history', [HistoryController::class, 'index'])->name('api.history.index');
Route::get('/history/{id}', [HistoryController::class, 'show'])->name('history.show');
Route::delete('/history/{id}', [HistoryController::class, 'destroy'])->name('history.destroy');

// About Route
Route::get('/about', function () {
    return view('about.index');
})->name('about');
