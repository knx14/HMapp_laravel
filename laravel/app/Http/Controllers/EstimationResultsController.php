<?php

namespace App\Http\Controllers;

use App\Models\AnalysisResult;
use App\Models\Farm;
use App\Models\ResultValue;
use App\Models\Upload;
use App\Support\SoilParameterUnits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EstimationResultsController extends Controller
{
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
            'parameterUnits' => SoilParameterUnits::map(),
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
            'parameters.*.name' => ['required', 'string', Rule::in(SoilParameterUnits::allowedNames())],
            'parameters.*.value' => 'required|numeric',
        ]);

        $validator->after(function ($validator) use ($request) {
            $names = collect($request->input('parameters', []))->pluck('name')->filter();
            if ($names->count() !== $names->unique()->count()) {
                $validator->errors()->add('parameters', 'パラメータ名が重複しています。');
            }
        });

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
                $parameterName = (string) $param['name'];
                ResultValue::create([
                    'analysis_result_id' => $analysisResultId,
                    'parameter_name' => $parameterName,
                    'parameter_value' => $param['value'],
                    'unit' => SoilParameterUnits::unitFor($parameterName),
                ]);
            }

            // Uploadのstatusをcompletedに更新
            $analysisResult->upload->update(['status' => Upload::STATUS_COMPLETED]);

            DB::commit();

            return redirect()->route('measurements.index')
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


