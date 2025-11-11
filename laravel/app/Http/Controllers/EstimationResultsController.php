<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Farm;
use App\Models\Upload;
use App\Models\AnalysisResult;
use App\Models\ResultValue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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

        // completedのものだけを取得（同一 measurement_date を1件に集約）
        $groupedDates = Upload::query()
            ->where('farm_id', $farm->id)
            ->where('status', Upload::STATUS_COMPLETED)
            ->selectRaw('measurement_date, MAX(id) as upload_id')
            ->groupBy('measurement_date')
            ->orderByDesc('measurement_date')
            ->get();

        // uploadedのものを取得（結果入力用 - 測定点入力前）
        $pendingUploads = Upload::where('farm_id', $farm->id)
            ->where('status', Upload::STATUS_UPLOADED)
            ->orderBy('measurement_date', 'desc')
            ->get();

        // processingのものを取得（結果入力用 - 測定点入力済み、測定値入力待ち）
        $processingUploads = Upload::where('farm_id', $farm->id)
            ->where('status', Upload::STATUS_PROCESSING)
            ->whereHas('analysisResult')
            ->with('analysisResult')
            ->orderBy('measurement_date', 'desc')
            ->get();

        return view('estimation_results.farm_dates', [
            'farm' => $farm,
            'groupedDates' => $groupedDates,
            'pendingUploads' => $pendingUploads,
            'processingUploads' => $processingUploads,
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

    /**
     * 結果入力ページを表示
     * status='uploaded'でfarmIdが一致するUploadを取得
     * または、AnalysisResultが既に存在する場合は直接ResultValue入力ページにリダイレクト
     */
    public function inputResult(Request $request, int $farmId)
    {
        $farm = Farm::findOrFail($farmId);

        // URLパラメータからupload_idを取得
        $selectedUploadId = $request->query('upload_id');

        // upload_idが指定されている場合、AnalysisResultが既に存在するかチェック
        if ($selectedUploadId) {
            $upload = Upload::where('id', $selectedUploadId)
                ->where('farm_id', $farmId)
                ->whereIn('status', [Upload::STATUS_UPLOADED, Upload::STATUS_PROCESSING])
                ->first();

            if ($upload && $upload->analysisResult) {
                // AnalysisResultが既に存在する場合は、直接ResultValue入力ページにリダイレクト
                return redirect()->route('estimation-results.input-result-value', [
                    'farm' => $farmId,
                    'analysisResult' => $upload->analysisResult->id
                ]);
            }
        }

        // status='uploaded'でfarmIdが一致するUploadを取得
        $pendingUploads = Upload::where('farm_id', $farmId)
            ->where('status', Upload::STATUS_UPLOADED)
            ->orderBy('measurement_date', 'desc')
            ->get();

        return view('estimation_results.input_result', [
            'farm' => $farm,
            'pendingUploads' => $pendingUploads,
            'selectedUploadId' => $selectedUploadId,
        ]);
    }

    /**
     * AnalysisResultを保存
     */
    public function storeAnalysisResult(Request $request, int $farmId)
    {
        $validator = Validator::make($request->all(), [
            'upload_id' => 'required|exists:uploads,id',
            'sensor_info' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // UploadがfarmIdと一致するか確認
        $upload = Upload::where('id', $request->upload_id)
            ->where('farm_id', $farmId)
            ->where('status', Upload::STATUS_UPLOADED)
            ->firstOrFail();

        // 圃場の境界線を取得
        $farm = Farm::findOrFail($farmId);
        $boundaryPolygon = $farm->boundary_polygon;

        if (!$boundaryPolygon || empty($boundaryPolygon)) {
            return redirect()->back()
                ->withErrors(['error' => 'この圃場には境界線データが設定されていません。'])
                ->withInput();
        }

        // 境界線データを正規化
        $polygon = $this->normalizePolygon($boundaryPolygon);

        // レイキャスティング法で圃場内かチェック
        if (!$this->isPointInPolygon($request->latitude, $request->longitude, $polygon)) {
            return redirect()->back()
                ->withErrors(['latitude' => '正しいデータ点を入力してください。入力された座標は圃場の境界外です。'])
                ->withInput();
        }

        // AnalysisResultを保存
        $analysisResult = AnalysisResult::create([
            'upload_id' => $request->upload_id,
            'sensor_info' => $request->sensor_info,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Uploadのstatusをprocessingに変更（測定点入力済み）
        $upload->update(['status' => Upload::STATUS_PROCESSING]);

        // 次のステップ（ResultValue入力）にリダイレクト
        return redirect()->route('estimation-results.input-result-value', [
            'farm' => $farmId,
            'analysisResult' => $analysisResult->id
        ])->with('success', '測定点が正常に登録されました。次に測定値を入力してください。');
    }

    /**
     * ResultValue入力ページを表示
     */
    public function inputResultValue(int $farmId, int $analysisResultId)
    {
        $farm = Farm::findOrFail($farmId);
        $analysisResult = AnalysisResult::with('upload')
            ->where('id', $analysisResultId)
            ->whereHas('upload', function ($query) use ($farmId) {
                $query->where('farm_id', $farmId);
            })
            ->firstOrFail();

        // 既存のResultValueを取得
        $existingValues = ResultValue::where('analysis_result_id', $analysisResultId)
            ->get()
            ->keyBy('parameter_name');

        return view('estimation_results.input_result_value', [
            'farm' => $farm,
            'analysisResult' => $analysisResult,
            'existingValues' => $existingValues,
        ]);
    }

    /**
     * ResultValueを保存
     */
    public function storeResultValue(Request $request, int $farmId, int $analysisResultId)
    {
        // AnalysisResultがfarmIdと一致するか確認
        $analysisResult = AnalysisResult::with('upload')
            ->where('id', $analysisResultId)
            ->whereHas('upload', function ($query) use ($farmId) {
                $query->where('farm_id', $farmId);
            })
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'parameters' => 'required|array',
            'parameters.*.name' => 'required|string|max:255',
            'parameters.*.value' => 'required|numeric',
            'parameters.*.unit' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // 既存のResultValueを削除
            ResultValue::where('analysis_result_id', $analysisResultId)->delete();

            // 新しいResultValueを保存
            foreach ($request->parameters as $param) {
                ResultValue::create([
                    'analysis_result_id' => $analysisResultId,
                    'parameter_name' => $param['name'],
                    'parameter_value' => $param['value'],
                    'unit' => $param['unit'] ?? null,
                ]);
            }

            // Uploadのstatusをcompletedに更新
            $analysisResult->upload->update(['status' => Upload::STATUS_COMPLETED]);

            DB::commit();

            return redirect()->route('estimation-results.farm-dates', ['farm' => $farmId])
                ->with('success', '測定値が正常に登録されました。');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => '測定値の登録中にエラーが発生しました。'])
                ->withInput();
        }
    }

    /**
     * 境界線データを正規化
     */
    private function normalizePolygon($boundaryData): array
    {
        $polygon = [];
        
        // boundary_polygonの構造を確認
        if (isset($boundaryData['boundary_polygon']) && is_array($boundaryData['boundary_polygon'])) {
            $boundaryData = $boundaryData['boundary_polygon'];
        }

        foreach ($boundaryData as $point) {
            if (is_array($point) && count($point) >= 2) {
                $polygon[] = [
                    'lat' => is_array($point) ? (float)$point[0] : (float)$point['lat'],
                    'lng' => is_array($point) ? (float)$point[1] : (float)$point['lng']
                ];
            } elseif (isset($point['lat']) && isset($point['lng'])) {
                $polygon[] = [
                    'lat' => (float)$point['lat'],
                    'lng' => (float)$point['lng']
                ];
            }
        }

        return $polygon;
    }

    /**
     * 点が多角形内にあるかチェック（レイキャスティングアルゴリズム）
     */
    private function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        if (empty($polygon)) {
            return false;
        }

        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            if (($polygon[$i]['lng'] > $lng) != ($polygon[$j]['lng'] > $lng) &&
                $lat < ($polygon[$j]['lat'] - $polygon[$i]['lat']) * ($lng - $polygon[$i]['lng']) / ($polygon[$j]['lng'] - $polygon[$i]['lng']) + $polygon[$i]['lat']) {
                $inside = !$inside;
            }
            $j = $i;
        }

        return $inside;
    }
}


