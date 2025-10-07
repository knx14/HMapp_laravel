<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FarmController extends Controller
{
    /**
     * 分析サマリー一覧API: 圃場と日付でグループ化
     */
    public function analysisSummary(): JsonResponse
    {
        $rows = DB::table('uploads')
            ->join('farms', 'uploads.farm_id', '=', 'farms.id')
            ->join('app_users', 'farms.app_user_id', '=', 'app_users.id')
            ->groupBy('farms.id', 'farms.farm_name', 'app_users.name', 'uploads.measurement_date')
            ->orderByDesc('uploads.measurement_date')
            ->get([
                DB::raw('farms.id as farm_id'),
                DB::raw('farms.farm_name'),
                DB::raw('app_users.name as owner_name'),
                DB::raw('uploads.measurement_date as date'),
                DB::raw('MAX(uploads.id) as upload_id'),
            ]);

        return response()->json($rows);
    }

    /**
     * 詳細分析データAPI: 指定uploadIdの全データを返す
     */
    public function analysisData(int $uploadId): JsonResponse
    {
        $upload = Upload::with([
            'farm:id,boundary_polygon',
            'analysisResults.resultValues',
        ])->findOrFail($uploadId);

        $boundaryPolygon = $upload->farm?->boundary_polygon ?? [];

        $analysisPoints = $upload->analysisResults->map(function ($ar) {
            return [
                'latitude' => (float) $ar->latitude,
                'longitude' => (float) $ar->longitude,
                'results' => $ar->resultValues->map(function ($rv) {
                    return [
                        'parameter' => $rv->parameter,
                        'value' => (float) $rv->value,
                        'unit' => $rv->unit,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'boundary_polygon' => $boundaryPolygon,
            'analysis_points' => $analysisPoints,
        ]);
    }
}


