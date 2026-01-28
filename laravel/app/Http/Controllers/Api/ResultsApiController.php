<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Farm;
use App\Models\Upload;
use App\Services\Results\ResultsAggregationService;
use Illuminate\Http\Request;

class ResultsApiController extends Controller
{
    public function __construct(private ResultsAggregationService $results) {}

    /**
     * GET /api/results/latest
     */
    public function latest(Request $request)
    {
        $user = $this->authUser($request);

        $farms = Farm::query()
            ->where('app_user_id', $user->id)
            ->get(['id', 'farm_name']);

        if ($farms->isEmpty()) {
            return response()->json([]);
        }

        $farmIds = $farms->pluck('id')->all();

        $latestByFarm = Upload::query()
            ->whereIn('farm_id', $farmIds)
            ->where('status', Upload::STATUS_COMPLETED)
            ->whereNotNull('measurement_date')
            ->selectRaw('farm_id, MAX(measurement_date) as latest_measurement_date')
            ->groupBy('farm_id')
            ->get()
            ->keyBy('farm_id');

        $out = [];
        foreach ($farms as $farm) {
            $row = $latestByFarm->get($farm->id);
            if (!$row) {
                continue; // latestがないfarmは /results/latest では返さない（仕様上 latest_measurement_date が必須）
            }

            $date = $this->results->toDateString($row->latest_measurement_date);
            $points = $this->results->fetchPointsForFarmDate((int) $farm->id, $date);
            $cecStats = $this->results->computeCecStats($points);

            $out[] = [
                'farm_id' => (int) $farm->id,
                'farm_name' => (string) $farm->farm_name,
                'latest_measurement_date' => $date,
                'cec_stats' => $cecStats,
                'summary_text' => $this->results->computeSummaryText($cecStats),
            ];
        }

        usort($out, function ($a, $b) {
            // latest_measurement_date desc
            return strcmp($b['latest_measurement_date'], $a['latest_measurement_date']);
        });

        return response()->json(array_values($out));
    }

    /**
     * GET /api/farms/with-latest-result
     */
    public function farmsWithLatestResult(Request $request)
    {
        $user = $this->authUser($request);

        $farms = Farm::query()
            ->where('app_user_id', $user->id)
            ->orderBy('id')
            ->get(['id', 'farm_name', 'boundary_polygon']);

        $out = [];
        foreach ($farms as $farm) {
            $latestDate = $this->results->getLatestCompletedDateForFarm((int) $farm->id);

            $latestResult = null;
            if ($latestDate !== null) {
                $points = $this->results->fetchPointsForFarmDate((int) $farm->id, $latestDate);
                $cecStats = $this->results->computeCecStats($points);
                $latestResult = [
                    'latest_measurement_date' => $latestDate,
                    'cec_stats' => $cecStats,
                    'summary_text' => $this->results->computeSummaryText($cecStats),
                ];
            }

            $out[] = [
                'farm_id' => (int) $farm->id,
                'farm_name' => (string) $farm->farm_name,
                'latest_result' => $latestResult,
            ];
        }

        return response()->json($out);
    }

    /**
     * GET /api/farms/{farm_id}/results/dates
     */
    public function farmResultDates(Request $request, int $farmId)
    {
        $farm = $this->authorizeFarm($request, $farmId);

        $dates = $this->results->getCompletedDistinctDatesForFarm((int) $farm->id);

        $out = [];
        foreach ($dates as $date) {
            $points = $this->results->fetchPointsForFarmDate((int) $farm->id, $date);
            $cecStats = $this->results->computeCecStats($points);
            $out[] = [
                'measurement_date' => $date,
                'cec_stats' => $cecStats,
                'summary_text' => $this->results->computeSummaryText($cecStats),
            ];
        }

        return response()->json($out);
    }

    /**
     * GET /api/farms/{farm_id}/results/map?date=YYYY-MM-DD
     */
    public function farmResultMap(Request $request, int $farmId)
    {
        $farm = $this->authorizeFarm($request, $farmId);

        $date = $this->requireDateParam($request);

        $points = $this->results->fetchPointsForFarmDate((int) $farm->id, $date);

        return response()->json([
            'farm' => [
                'farm_id' => (int) $farm->id,
                'farm_name' => (string) $farm->farm_name,
                'boundary_polygon' => $this->results->normalizeBoundaryPolygon($farm->boundary_polygon),
            ],
            'measurement_date' => $date,
            'points' => $points,
        ]);
    }

    /**
     * GET /api/farms/{farm_id}/results/map-diff?date=YYYY-MM-DD
     */
    public function farmResultMapDiff(Request $request, int $farmId)
    {
        $farm = $this->authorizeFarm($request, $farmId);

        $date = $this->requireDateParam($request);

        $previousDate = Upload::query()
            ->where('farm_id', $farm->id)
            ->where('status', Upload::STATUS_COMPLETED)
            ->whereNotNull('measurement_date')
            ->whereDate('measurement_date', '<', $date)
            ->max('measurement_date');

        if (!$previousDate) {
            return response()->json(['message' => 'previous_not_found'], 404);
        }

        $previousDateStr = $this->results->toDateString($previousDate);

        $currentPoints = $this->results->fetchPointsForFarmDate((int) $farm->id, $date);
        $previousPoints = $this->results->fetchPointsForFarmDate((int) $farm->id, $previousDateStr);

        $outPoints = [];
        foreach ($currentPoints as $cp) {
            $matchedPrev = $this->findNearestWithin3m($cp, $previousPoints);

            $currentValues = $cp['values'] ?? [];
            $prevValues = $matchedPrev ? ($matchedPrev['values'] ?? []) : null;

            $prevMap = [];
            if (is_array($prevValues)) {
                foreach ($prevValues as $v) {
                    if (isset($v['parameter'])) {
                        $prevMap[(string) $v['parameter']] = $v;
                    }
                }
            }

            $diffValues = [];
            foreach ($currentValues as $cv) {
                $param = (string) ($cv['parameter'] ?? '');
                $unit = $cv['unit'] ?? null;
                $diff = null;

                if ($matchedPrev && $param !== '' && isset($prevMap[$param])) {
                    $curVal = $cv['value'] ?? null;
                    $prevVal = $prevMap[$param]['value'] ?? null;
                    if ($curVal !== null && $prevVal !== null) {
                        $diff = (float) $curVal - (float) $prevVal;
                    }
                }

                $diffValues[] = [
                    'parameter' => $param,
                    'diff_value' => $diff,
                    'unit' => $unit,
                ];
            }

            $outPoints[] = [
                'point_id' => (int) $cp['point_id'],
                'lat' => $cp['lat'],
                'lng' => $cp['lng'],
                'current_values' => $currentValues,
                'previous_values' => $prevValues,
                'diff_values' => $diffValues,
            ];
        }

        return response()->json([
            'farm' => [
                'farm_id' => (int) $farm->id,
                'farm_name' => (string) $farm->farm_name,
                'boundary_polygon' => $this->results->normalizeBoundaryPolygon($farm->boundary_polygon),
            ],
            'measurement_date' => $date,
            'previous_measurement_date' => $previousDateStr,
            'points' => $outPoints,
        ]);
    }

    private function authUser(Request $request): AppUser
    {
        $user = $request->attributes->get('auth_user');
        if (!$user instanceof AppUser) {
            abort(response()->json(['message' => 'Unauthenticated'], 401));
        }
        return $user;
    }

    private function authorizeFarm(Request $request, int $farmId): Farm
    {
        $user = $this->authUser($request);

        $farm = Farm::find($farmId);
        if (!$farm) {
            abort(404);
        }
        if ((int) $farm->app_user_id !== (int) $user->id) {
            abort(403);
        }

        return $farm;
    }

    private function requireDateParam(Request $request): string
    {
        $date = (string) $request->query('date', '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(response()->json(['message' => 'invalid_date'], 422));
        }

        // 厳密な日付として解釈できるか
        try {
            $dt = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
            if ($dt->format('Y-m-d') !== $date) {
                abort(response()->json(['message' => 'invalid_date'], 422));
            }
        } catch (\Throwable $e) {
            abort(response()->json(['message' => 'invalid_date'], 422));
        }

        return $date;
    }

    /**
     * 地点マッチング（仕様固定）:
     * - 当日pointsを基準に前回pointsから最近傍を1つ選ぶ
     * - Haversine距離 <= 3.0m の場合のみマッチ成功
     *
     * @param array{lat: float|null, lng: float|null} $currentPoint
     * @param array<int, array{lat: float|null, lng: float|null}> $previousPoints
     * @return array|null
     */
    private function findNearestWithin3m(array $currentPoint, array $previousPoints): ?array
    {
        $lat = $currentPoint['lat'] ?? null;
        $lng = $currentPoint['lng'] ?? null;

        if ($lat === null || $lng === null) {
            return null;
        }

        $best = null;
        $bestDist = null;

        foreach ($previousPoints as $pp) {
            $plat = $pp['lat'] ?? null;
            $plng = $pp['lng'] ?? null;
            if ($plat === null || $plng === null) {
                continue;
            }

            $d = $this->results->haversineMeters((float) $lat, (float) $lng, (float) $plat, (float) $plng);
            if ($bestDist === null || $d < $bestDist) {
                $bestDist = $d;
                $best = $pp;
            }
        }

        if ($bestDist !== null && $bestDist <= 3.0) {
            return $best;
        }

        return null;
    }
}

