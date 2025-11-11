<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Farm;
use App\Models\AppUser;
use App\Models\AnalysisResult;
use App\Models\ResultValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FarmManagementController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->only(['cultivation_method', 'crop_type']);

        $query = Farm::with('appUser');

        // 栽培方法で検索
        if (!empty($input['cultivation_method'])) {
            $query->where('cultivation_method', 'like', '%' . $input['cultivation_method'] . '%');
        }

        // 作物種別で検索
        if (!empty($input['crop_type'])) {
            $query->where('crop_type', 'like', '%' . $input['crop_type'] . '%');
        }

        $farms = $query->orderBy('id')->paginate(10);

        return view('farm_management.index', [
            'farms' => $farms,
            'input' => $input,
        ]);
    }

    /**
     * 圃場登録フォームを表示
     */
    public function create()
    {
        return view('farm_management.create');
    }

    /**
     * 圃場を登録
     */
    public function store(Request $request)
    {
        // バリデーションルール
        $rules = [
            'owner_name' => 'required|string|max:255',
            'farm_name' => 'required|string|max:255',
            'cultivation_method' => 'nullable|string|max:255',
            'crop_type' => 'nullable|string|max:255',
        ];

        // GPS座標のバリデーションルールを追加
        for ($i = 1; $i <= 4; $i++) {
            $rules["gps_lat_{$i}"] = 'required|numeric|between:-90,90';
            $rules["gps_lng_{$i}"] = 'required|numeric|between:-180,180';
        }

        // オプションのGPS座標（5-8点目）
        for ($i = 5; $i <= 8; $i++) {
            $rules["gps_lat_{$i}"] = 'nullable|numeric|between:-90,90';
            $rules["gps_lng_{$i}"] = 'nullable|numeric|between:-180,180';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // 保有者名からapp_user_idを取得
        $appUser = AppUser::where('name', $request->owner_name)->first();
        
        if (!$appUser) {
            return redirect()->back()
                ->withErrors(['owner_name' => '指定された保有者名が見つかりません。'])
                ->withInput();
        }

        // GPS座標を収集
        $gpsCoordinates = [];
        for ($i = 1; $i <= 8; $i++) {
            $lat = $request->input("gps_lat_{$i}");
            $lng = $request->input("gps_lng_{$i}");
            
            if ($lat && $lng) {
                $gpsCoordinates[] = [(float)$lat, (float)$lng];
            }
        }

        // 最低4点のGPS座標が必要
        if (count($gpsCoordinates) < 4) {
            return redirect()->back()
                ->withErrors(['gps_coordinates' => '最低4点のGPS座標が必要です。'])
                ->withInput();
        }

        // GPS座標を時計回りにソート
        $gpsCoordinates = $this->sortCoordinatesClockwise($gpsCoordinates);

        // 圃場データを作成
        $farmData = [
            'app_user_id' => $appUser->id,
            'farm_name' => $request->farm_name,
            'cultivation_method' => $request->cultivation_method,
            'crop_type' => $request->crop_type,
            'boundary_polygon' => [
                'boundary_polygon' => $gpsCoordinates
            ]
        ];

        try {
            Farm::create($farmData);
            
            return redirect()->route('farm-management.index')
                ->with('success', '圃場が正常に登録されました。');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => '圃場の登録中にエラーが発生しました。'])
                ->withInput();
        }
    }



    /**
     * 指定された圃場の境界線データを取得する
     *
     * @param int $farmId
     * @return JsonResponse
     */
    public function getBoundary(int $farmId): JsonResponse
    {
        $farm = Farm::find($farmId);

        if (!$farm) {
            return response()->json([
                'error' => '指定された圃場が見つかりません。',
                'message' => 'Farm not found'
            ], 404)->header('Access-Control-Allow-Origin', '*')
                   ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                   ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        if (!$farm->boundary_polygon) {
            return response()->json([
                'error' => 'この圃場には境界線データが設定されていません。',
                'message' => 'No boundary data available'
            ], 404)->header('Access-Control-Allow-Origin', '*')
                   ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                   ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'farm_id' => $farm->id,
                'farm_name' => $farm->farm_name,
                'boundary_polygon' => $farm->boundary_polygon
            ]
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * 指定された圃場内の測定データを取得する
     * データベースのリレーションシップを使用して直接取得
     *
     * @param int $farmId
     * @return JsonResponse
     */
    public function getFarmMeasurements(int $farmId): JsonResponse
    {
        $farm = Farm::find($farmId);

        if (!$farm) {
            return response()->json([
                'error' => '指定された圃場が見つかりません。',
                'message' => 'Farm not found'
            ], 404)->header('Access-Control-Allow-Origin', '*')
                   ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                   ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        // データベースのリレーションシップを使用して圃場に関連する測定データを取得
        // AnalysisResult → Upload → Farm のリレーションを利用
        $analysisResults = AnalysisResult::with('resultValues')
            ->whereHas('upload', function ($query) use ($farmId) {
                $query->where('farm_id', $farmId);
            })
            ->get();

        $measurements = [];

        foreach ($analysisResults as $result) {
            $measurementData = [
                'id' => $result->id,
                'latitude' => $result->latitude,
                'longitude' => $result->longitude,
                'sensor_info' => $result->sensor_info,
                'values' => []
            ];

            // 各測定値を取得
            foreach ($result->resultValues as $value) {
                $measurementData['values'][$value->parameter_name] = [
                    'value' => $value->parameter_value,
                    'unit' => $value->unit ?? null
                ];
            }

            $measurements[] = $measurementData;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'farm_id' => $farm->id,
                'farm_name' => $farm->farm_name,
                'measurements' => $measurements
            ]
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * GPS座標を時計回りにソートする
     * 中心点からの角度を計算してソートすることで、登録順に関わらず
     * 正しい順序でポリゴンが描画されるようにする
     * 
     * @param array $coordinates [[lat, lng], ...] の形式
     * @return array ソート済み座標配列
     */
    private function sortCoordinatesClockwise(array $coordinates): array
    {
        if (count($coordinates) < 3) {
            return $coordinates;
        }

        // 1. 中心点を計算（全ての点の平均）
        $sumLat = 0;
        $sumLng = 0;
        foreach ($coordinates as $coord) {
            $sumLat += $coord[0];  // lat
            $sumLng += $coord[1];  // lng
        }
        $centerLat = $sumLat / count($coordinates);
        $centerLng = $sumLng / count($coordinates);

        // 2. 各点を中心からの角度でソート（時計回り = 降順）
        usort($coordinates, function ($a, $b) use ($centerLat, $centerLng) {
            // 点aの角度を計算
            $deltaLatA = $a[0] - $centerLat;
            $deltaLngA = $a[1] - $centerLng;
            $angleA = atan2($deltaLatA, $deltaLngA);
            
            // 点bの角度を計算
            $deltaLatB = $b[0] - $centerLat;
            $deltaLngB = $b[1] - $centerLng;
            $angleB = atan2($deltaLatB, $deltaLngB);
            
            // 角度が同じ場合（一直線上の点）は距離でソート（近い順）
            if (abs($angleA - $angleB) < 0.0001) {
                $distanceA = sqrt($deltaLatA * $deltaLatA + $deltaLngA * $deltaLngA);
                $distanceB = sqrt($deltaLatB * $deltaLatB + $deltaLngB * $deltaLngB);
                return $distanceB <=> $distanceA;  // 距離の降順（外側の点を先に）
            }
            
            // 降順でソート（時計回り）
            return $angleB <=> $angleA;
        });

        return $coordinates;
    }
}
