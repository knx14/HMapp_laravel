<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvUploadController;
use App\Http\Controllers\RdsSearchController;

Route::get('/', function () {
    return view('welcome'); // Breeze で提供されている認証トップページ
})->middleware('guest');
// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/csv/upload', [CsvUploadController::class, 'create'])->name('csv.upload');
    Route::post('/csv/upload', [CsvUploadController::class, 'store'])->name('csv.upload.store');
    
    // RDS検索ルート
    Route::get('/rds/search', [RdsSearchController::class, 'index'])->name('rds.search');
    Route::get('/rds/search/execute', [RdsSearchController::class, 'search'])->name('rds.search.execute');
    Route::get('/rds/search/api', [RdsSearchController::class, 'searchApi'])->name('rds.search.api');
});

require __DIR__.'/auth.php';
