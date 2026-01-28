<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\FarmManagementController;

Route::get('/', function () {
    return view('auth.auth');
});
// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
	Route::get('/users', [UserManagementController::class, 'index'])->name('user-management.index');
	Route::get('/farms', [FarmManagementController::class, 'index'])->name('farm-management.index');
	Route::get('/farms/create', [FarmManagementController::class, 'create'])->name('farm-management.create');
	Route::post('/farms', [FarmManagementController::class, 'store'])->name('farm-management.store');
	Route::get('/uploads', [App\Http\Controllers\UploadManagementController::class, 'index'])->name('upload-management.index');
	Route::get('/uploads/create', [App\Http\Controllers\UploadManagementController::class, 'create'])->name('upload-management.create');
	Route::post('/uploads', [App\Http\Controllers\UploadManagementController::class, 'store'])->name('upload-management.store');
    // 推定結果閲覧
    Route::get('/estimation-results', [\App\Http\Controllers\EstimationResultsController::class, 'index'])->name('estimation-results.index');
    Route::get('/estimation-results/farms/{farm}', [\App\Http\Controllers\EstimationResultsController::class, 'farmDates'])
        ->whereNumber('farm')
        ->name('estimation-results.farm-dates');
    Route::get('/estimation-results/farms/{farm}/uploads/{upload}', [\App\Http\Controllers\EstimationResultsController::class, 'cecMap'])
        ->whereNumber('farm')
        ->whereNumber('upload')
        ->name('estimation-results.cec');
    
    // 結果入力
    Route::get('/estimation-results/farms/{farm}/input', [\App\Http\Controllers\EstimationResultsController::class, 'inputResult'])
        ->whereNumber('farm')
        ->name('estimation-results.input');
    Route::post('/estimation-results/farms/{farm}/analysis-result', [\App\Http\Controllers\EstimationResultsController::class, 'storeAnalysisResult'])
        ->whereNumber('farm')
        ->name('estimation-results.store-analysis-result');
    Route::get('/estimation-results/farms/{farm}/analysis-results/{analysisResult}/input-value', [\App\Http\Controllers\EstimationResultsController::class, 'inputResultValue'])
        ->whereNumber('farm')
        ->whereNumber('analysisResult')
        ->name('estimation-results.input-result-value');
    Route::post('/estimation-results/farms/{farm}/analysis-results/{analysisResult}/result-value', [\App\Http\Controllers\EstimationResultsController::class, 'storeResultValue'])
        ->whereNumber('farm')
        ->whereNumber('analysisResult')
        ->name('estimation-results.store-result-value');
});

// 圃場の境界線データを取得するAPIエンドポイント（認証不要）
Route::get('/api/farms/{farmId}/boundary', [FarmManagementController::class, 'getBoundary']);
// 圃場内の測定データを取得するAPIエンドポイント（認証不要）
Route::get('/api/farms/{farmId}/measurements', [FarmManagementController::class, 'getFarmMeasurements']);

require __DIR__.'/auth.php';
