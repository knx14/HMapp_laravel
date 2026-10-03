<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\FarmManagementController;
use App\Http\Controllers\FarmWorkLogController;
use App\Http\Controllers\EstimationResultsController;
use App\Models\Upload;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\UploadManagementController;

Route::get('/', function () {
    $user = auth()->user();

    return $user
        ? redirect(AuthenticatedSessionController::homeUrl($user))
        : redirect()->route('login');
});

// 所属の入力（所属が未入力でも開ける）
Route::middleware('auth')->group(function () {
    Route::get('/organization', [OrganizationController::class, 'edit'])->name('organization.edit');
    Route::put('/organization', [OrganizationController::class, 'update'])->name('organization.update');
});

// 一般ユーザー・管理者の両方が使う画面（表示範囲はコントローラーで絞る）
Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/name', [ProfileController::class, 'updateName'])->name('profile.name');
    Route::post('/profile/email', [ProfileController::class, 'requestEmailChange'])->name('profile.email');
    Route::post('/profile/email/verify', [ProfileController::class, 'confirmEmailChange'])->name('profile.email.verify');
    Route::delete('/profile/email/pending', [ProfileController::class, 'cancelEmailChange'])->name('profile.email.cancel');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/admin-key', [ProfileController::class, 'grantAdmin'])->name('profile.admin-key');

    Route::get('/farms', [FarmManagementController::class, 'index'])->name('farm-management.index');
    Route::get('/farms/create', [FarmManagementController::class, 'create'])->name('farm-management.create');
    Route::post('/farms', [FarmManagementController::class, 'store'])->name('farm-management.store');
    Route::get('/farms/{farm}', [FarmManagementController::class, 'show'])
        ->whereNumber('farm')
        ->name('farm-management.show');
    Route::post('/farms/{farm}/work-logs', [FarmWorkLogController::class, 'store'])
        ->whereNumber('farm')
        ->name('farm-management.work-logs.store');
    Route::put('/farms/{farm}/work-logs/{workLog}', [FarmWorkLogController::class, 'update'])
        ->whereNumber('farm')
        ->whereNumber('workLog')
        ->name('farm-management.work-logs.update');
    Route::delete('/farms/{farm}/work-logs/{workLog}', [FarmWorkLogController::class, 'destroy'])
        ->whereNumber('farm')
        ->whereNumber('workLog')
        ->name('farm-management.work-logs.destroy');
    Route::get('/farms/{farm}/edit', [FarmManagementController::class, 'edit'])
        ->whereNumber('farm')
        ->name('farm-management.edit');
    Route::put('/farms/{farm}', [FarmManagementController::class, 'update'])
        ->whereNumber('farm')
        ->name('farm-management.update');
    Route::delete('/farms/{farm}', [FarmManagementController::class, 'destroy'])
        ->whereNumber('farm')
        ->name('farm-management.destroy');

    // 測定データ閲覧
    Route::get('/measurements', [MeasurementController::class, 'index'])->name('measurements.index');
    Route::post('/measurements/export', [MeasurementController::class, 'export'])->name('measurements.export');
    Route::post('/measurements/delete', [MeasurementController::class, 'destroySelected'])->name('measurements.destroy-selected');
    Route::get('/measurements/{upload}', [MeasurementController::class, 'show'])
        ->whereNumber('upload')
        ->name('measurements.show');
    Route::patch('/measurements/{upload}/location', [MeasurementController::class, 'updateLocation'])
        ->whereNumber('upload')
        ->name('measurements.location');

    // 旧「推定結果閲覧」は圃場詳細へ移した
    Route::get('/estimation-results', fn () => redirect()->route('farm-management.index'))
        ->name('estimation-results.index');
    Route::get('/estimation-results/farms/{farm}', fn (int $farm) => redirect()->route('farm-management.show', $farm))
        ->whereNumber('farm')
        ->name('estimation-results.farm-dates');
    Route::get('/estimation-results/farms/{farm}/uploads/{upload}', function (int $farm, int $upload) {
        $row = Upload::query()->where('farm_id', $farm)->findOrFail($upload);
        $date = $row->measurement_date?->format('Y-m-d');

        return redirect()->route('farm-management.show', array_filter([
            'farm' => $farm,
            'date' => $date,
        ]));
    })
        ->whereNumber('farm')
        ->whereNumber('upload')
        ->name('estimation-results.cec');

    // 圃場の境界線データ（圃場管理画面の地図表示用）
    Route::get('/api/farms/{farmId}/boundary', [FarmManagementController::class, 'getBoundary'])
        ->whereNumber('farmId');
});

// 管理者専用の画面
Route::middleware(['auth', 'organization', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserManagementController::class, 'index'])->name('user-management.index');
    Route::post('/users/export', [UserManagementController::class, 'export'])->name('user-management.export');
    Route::post('/users/delete', [UserManagementController::class, 'destroySelected'])->name('user-management.destroy-selected');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('user-management.show');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('user-management.update');
    Route::post('/users/{user}/revoke-admin', [UserManagementController::class, 'revokeAdmin'])->name('user-management.revoke-admin');

    Route::put('/measurements/{upload}', [MeasurementController::class, 'update'])
        ->whereNumber('upload')
        ->name('measurements.update');

    // 旧アップロード管理の一覧は測定データ閲覧に統合した
    Route::redirect('/uploads', '/measurements')->name('upload-management.index');
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
