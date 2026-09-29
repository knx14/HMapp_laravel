<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\FarmManagementController;
use App\Http\Controllers\EstimationResultsController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\UploadManagementController;

Route::get('/', function () {
    $user = auth()->user();

    return $user
        ? redirect(AuthenticatedSessionController::homeUrl($user))
        : redirect()->route('login');
});

// 一般ユーザー・管理者の両方が使う画面（表示範囲はコントローラーで絞る）
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/admin-key', [ProfileController::class, 'grantAdmin'])->name('profile.admin-key');

    Route::get('/farms', [FarmManagementController::class, 'index'])->name('farm-management.index');

    // 測定データ閲覧
    Route::get('/measurements', [MeasurementController::class, 'index'])->name('measurements.index');
    Route::post('/measurements/export', [MeasurementController::class, 'export'])->name('measurements.export');

    // 推定結果閲覧
    Route::get('/estimation-results', [EstimationResultsController::class, 'index'])->name('estimation-results.index');
    Route::get('/estimation-results/farms/{farm}', [EstimationResultsController::class, 'farmDates'])
        ->whereNumber('farm')
        ->name('estimation-results.farm-dates');
    Route::get('/estimation-results/farms/{farm}/uploads/{upload}', [EstimationResultsController::class, 'cecMap'])
        ->whereNumber('farm')
        ->whereNumber('upload')
        ->name('estimation-results.cec');

    // 圃場の境界線データ（圃場管理画面の地図表示用）
    Route::get('/api/farms/{farmId}/boundary', [FarmManagementController::class, 'getBoundary'])
        ->whereNumber('farmId');
});

// 管理者専用の画面
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserManagementController::class, 'index'])->name('user-management.index');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('user-management.show');
    Route::post('/users/{user}/revoke-admin', [UserManagementController::class, 'revokeAdmin'])->name('user-management.revoke-admin');

    Route::get('/farms/create', [FarmManagementController::class, 'create'])->name('farm-management.create');
    Route::post('/farms', [FarmManagementController::class, 'store'])->name('farm-management.store');

    Route::get('/uploads', [UploadManagementController::class, 'index'])->name('upload-management.index');
    Route::get('/uploads/create', [UploadManagementController::class, 'create'])->name('upload-management.create');
    Route::post('/uploads', [UploadManagementController::class, 'store'])->name('upload-management.store');
    Route::get('/uploads/download', [UploadManagementController::class, 'download'])->name('upload-management.download');

    // 結果入力
    Route::get('/estimation-results/farms/{farm}/input', [EstimationResultsController::class, 'inputResult'])
        ->whereNumber('farm')
        ->name('estimation-results.input');
    Route::post('/estimation-results/farms/{farm}/analysis-result', [EstimationResultsController::class, 'storeAnalysisResult'])
        ->whereNumber('farm')
        ->name('estimation-results.store-analysis-result');
    Route::get('/estimation-results/farms/{farm}/analysis-results/{analysisResult}/input-value', [EstimationResultsController::class, 'inputResultValue'])
        ->whereNumber('farm')
        ->whereNumber('analysisResult')
        ->name('estimation-results.input-result-value');
    Route::post('/estimation-results/farms/{farm}/analysis-results/{analysisResult}/result-value', [EstimationResultsController::class, 'storeResultValue'])
        ->whereNumber('farm')
        ->whereNumber('analysisResult')
        ->name('estimation-results.store-result-value');
});

require __DIR__.'/auth.php';
