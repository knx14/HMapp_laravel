<?php

namespace App\Services\Results;

use App\Models\AnalysisResult;
use App\Models\ResultValue;
use App\Models\Upload;

class ResultsAggregationService
{
    /**
     * @return array<int> upload ids
     */
    public function getCompletedUploadIdsForFarmDate(int $farmId, string $measurementDate): array
    {
        return Upload::query()
            ->where('farm_id', $farmId)
            ->where('status', Upload::STATUS_COMPLETED)
            ->whereDate('measurement_date', $measurementDate)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, string> date strings "YYYY-MM-DD" desc
     */
    public function getCompletedDistinctDatesForFarm(int $farmId): array
    {
        $rows = Upload::query()
            ->where('farm_id', $farmId)
            ->where('status', Upload::STATUS_COMPLETED)
            ->whereNotNull('measurement_date')
            ->selectRaw('measurement_date')
            ->groupBy('measurement_date')
            ->orderByDesc('measurement_date')
            ->get();

        return $rows
            ->map(fn ($r) => $this->toDateString($r->measurement_date))
            ->values()
            ->all();
    }

    public function getLatestCompletedDateForFarm(int $farmId): ?string
    {
        $date = Upload::query()
            ->where('farm_id', $farmId)
            ->where('status', Upload::STATUS_COMPLETED)
            ->whereNotNull('measurement_date')
            ->max('measurement_date');

        return $date ? $this->toDateString($date) : null;
    }

    /**
     * @param mixed $measurementDate
     */
    public function toDateString($measurementDate): string
    {
        // Upload::measurement_date is cast to date, but SQL aggregates may return string.
        if ($measurementDate instanceof \Carbon\CarbonInterface) {
            return $measurementDate->toDateString();
        }
        return (string) $measurementDate;
    }

    /**
     * boundary_polygonがnull/空なら [] を返す（仕様固定）
     *
     * @param mixed $boundaryPolygon
     * @return array<int, array{lat: float, lng: float}>
     */
    public function normalizeBoundaryPolygon($boundaryPolygon): array
    {
        if (empty($boundaryPolygon) || !is_array($boundaryPolygon)) {
            return [];
        }

        // 既存データの揺れ対応: {"boundary_polygon":[...]} の場合
        if (isset($boundaryPolygon['boundary_polygon']) && is_array($boundaryPolygon['boundary_polygon'])) {
            $boundaryPolygon = $boundaryPolygon['boundary_polygon'];
        }

        $out = [];
        foreach ($boundaryPolygon as $p) {
            if (is_array($p) && array_is_list($p) && count($p) >= 2) {
                $lat = $p[0];
                $lng = $p[1];
            } elseif (is_array($p) && isset($p['lat'], $p['lng'])) {
                $lat = $p['lat'];
                $lng = $p['lng'];
            } else {
                continue;
            }

            if ($lat === null || $lng === null) {
                continue;
            }

            $out[] = [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            ];
        }

        return $out;
    }

    /**
     * 指定farm_id + measurement_dateの completed uploads を同日集約し、地点(points)を返す。
     *
     * @return array<int, array{
     *   point_id:int,
     *   lat: float|null,
     *   lng: float|null,
     *   values: array<int, array{parameter:string, value: float|null, unit: string|null}>
     * }>
     */
    public function fetchPointsForFarmDate(int $farmId, string $measurementDate): array
    {
        $uploadIds = $this->getCompletedUploadIdsForFarmDate($farmId, $measurementDate);
        if (count($uploadIds) === 0) {
            return [];
        }

        $analysisPoints = AnalysisResult::query()
            ->whereIn('upload_id', $uploadIds)
            ->get(['id', 'latitude', 'longitude']);

        if ($analysisPoints->isEmpty()) {
            return [];
        }

        $analysisIds = $analysisPoints->pluck('id')->all();

        $valuesByPoint = ResultValue::query()
            ->whereIn('analysis_result_id', $analysisIds)
            ->orderBy('parameter_name')
            ->get(['analysis_result_id', 'parameter_name', 'parameter_value', 'unit'])
            ->groupBy('analysis_result_id');

        return $analysisPoints->map(function ($p) use ($valuesByPoint) {
            $valuesForPoint = $valuesByPoint->get($p->id, collect());

            return [
                'point_id' => (int) $p->id,
                'lat' => is_null($p->latitude) ? null : (float) $p->latitude,
                'lng' => is_null($p->longitude) ? null : (float) $p->longitude,
                'values' => $valuesForPoint->map(function ($rv) {
                    return [
                        'parameter' => (string) $rv->parameter_name,
                        'value' => is_null($rv->parameter_value) ? null : (float) $rv->parameter_value,
                        'unit' => $rv->unit ?? null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * CEC統計: count_pointsは「地点数(analysis_results件数)」で固定。
     * avg/min/maxはCECが存在する地点のみで計算（全欠損ならnull）。
     *
     * @param array<int, array{values: array<int, array{parameter:string, value: float|null}>}> $points
     * @return array{avg: float|null, min: float|null, max: float|null, count_points:int}
     */
    public function computeCecStats(array $points): array
    {
        $countPoints = count($points);
        $cecValues = [];

        foreach ($points as $p) {
            foreach (($p['values'] ?? []) as $v) {
                if (($v['parameter'] ?? null) === 'CEC') {
                    $val = $v['value'] ?? null;
                    if ($val !== null) {
                        $cecValues[] = (float) $val;
                    }
                    break;
                }
            }
        }

        if (count($cecValues) === 0) {
            return [
                'avg' => null,
                'min' => null,
                'max' => null,
                'count_points' => $countPoints,
            ];
        }

        $min = min($cecValues);
        $max = max($cecValues);
        $avg = array_sum($cecValues) / count($cecValues);

        return [
            'avg' => (float) $avg,
            'min' => (float) $min,
            'max' => (float) $max,
            'count_points' => $countPoints,
        ];
    }

    /**
     * summary_text生成（仕様固定）
     * - min/maxがnullなら "データ不足"
     */
    public function computeSummaryText(array $cecStats): string
    {
        $min = $cecStats['min'] ?? null;
        $max = $cecStats['max'] ?? null;

        if ($min === null || $max === null) {
            return 'データ不足';
        }

        $range = (float) $max - (float) $min;

        if ($range < 2.0) {
            return 'ばらつき小';
        }
        if ($range < 5.0) {
            return 'ややばらつき';
        }
        return 'ばらつき大';
    }

    /**
     * Haversine距離（メートル）
     */
    public function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000.0; // earth radius (m)
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2 - $lat1);
        $dLambda = deg2rad($lng2 - $lng1);

        $a = sin($dPhi / 2) ** 2 + cos($phi1) * cos($phi2) * (sin($dLambda / 2) ** 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }
}

