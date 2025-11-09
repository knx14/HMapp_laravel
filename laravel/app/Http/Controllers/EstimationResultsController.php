<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Farm;
use App\Models\Upload;
use App\Models\AnalysisResult;
use App\Models\ResultValue;

class EstimationResultsController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->only(['cultivation_method', 'crop_type']);

        $query = Farm::with('appUser');

        if (!empty($input['cultivation_method'])) {
            $query->where('cultivation_method', 'like', '%' . $input['cultivation_method'] . '%');
        }

        if (!empty($input['crop_type'])) {
            $query->where('crop_type', 'like', '%' . $input['crop_type'] . '%');
        }

        $farms = $query->orderBy('id')->paginate(10);

        return view('estimation_results.index', [
            'farms' => $farms,
            'input' => $input,
        ]);
    }

    public function farmDates(int $farmId)
    {
        $farm = Farm::findOrFail($farmId);

        // 同一 measurement_date を1件に集約（代表として最大IDを採用）
        $groupedDates = Upload::query()
            ->where('farm_id', $farm->id)
            ->selectRaw('measurement_date, MAX(id) as upload_id')
            ->groupBy('measurement_date')
            ->orderByDesc('measurement_date')
            ->get();

        return view('estimation_results.farm_dates', [
            'farm' => $farm,
            'groupedDates' => $groupedDates,
        ]);
    }

    public function cecMap(int $farmId, int $uploadId)
    {
        $farm = Farm::findOrFail($farmId);
        $upload = Upload::where('id', $uploadId)->where('farm_id', $farm->id)->firstOrFail();

        // 選択した日付と同じ日付のアップロードIDを取得（同じ日に複数点取得したデータをまとめて表示）
        $uploadIds = Upload::where('farm_id', $farm->id)
            ->where('measurement_date', $upload->measurement_date)
            ->pluck('id');

        // analysis_results から該当アップロード群の座標とIDを取得
        $analysisPoints = AnalysisResult::whereIn('upload_id', $uploadIds)
            ->get(['id', 'latitude', 'longitude']);

        $analysisIds = $analysisPoints->pluck('id')->all();

        // result_values から全パラメータを取得してIDごとにグルーピング
        $allValues = ResultValue::whereIn('analysis_result_id', $analysisIds)
            ->get(['analysis_result_id', 'parameter_name', 'parameter_value', 'unit'])
            ->groupBy('analysis_result_id');

        // フロントに渡す形 {lat, lng, values: [{parameter, value, unit}], cec} の配列
        $points = $analysisPoints->map(function ($p) use ($allValues) {
            $valuesForPoint = $allValues->get($p->id, collect());
            $cecValue = optional($valuesForPoint->firstWhere('parameter_name', 'CEC'))->parameter_value;
            return [
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'cec' => is_null($cecValue) ? null : (float) $cecValue,
                'values' => $valuesForPoint->map(function ($rv) {
                    return [
                        'parameter' => $rv->parameter_name,
                        'value' => (float) $rv->parameter_value,
                        'unit' => $rv->unit ?? null,
                    ];
                })->values(),
            ];
        })->values();

        $boundaryPolygon = $farm->boundary_polygon ?? [];

        return view('estimation_results.cec_map', [
            'farm' => $farm,
            'upload' => $upload,
            'boundaryPolygon' => $boundaryPolygon,
            'points' => $points,
        ]);
    }
}


