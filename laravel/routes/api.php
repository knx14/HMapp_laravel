<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FarmManagementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API v1 ルート（より具体的なルートを先に定義）
Route::prefix('v1')->group(function () {
    require base_path('routes/api/v1.php');
});

// 分析サマリー一覧API
Route::get('/analysis/summary', [FarmController::class, 'analysisSummary']);

// 詳細分析データAPI
Route::get('/uploads/{uploadId}/analysis-data', [FarmController::class, 'analysisData']);

// 圃場の境界線データを取得するAPIエンドポイント（認証不要）
Route::get('/farms/{farmId}/boundary', [FarmManagementController::class, 'getBoundary'])->middleware('web');