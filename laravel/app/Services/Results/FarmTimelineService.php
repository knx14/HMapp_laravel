<?php

namespace App\Services\Results;

use App\Models\Upload;
use Illuminate\Support\Facades\DB;

class FarmTimelineService
{
    private const TARGET_PARAMETERS = ['CEC', 'CaO', 'K2O', 'MgO'];

    public function get(int $farmId): array
    {
        $pointCountRows = DB::select('
            SELECT u.measurement_date AS date, COUNT(DISTINCT ar.id) AS count_points
            FROM uploads u
            INNER JOIN analysis_results ar ON ar.upload_id = u.id
            WHERE u.farm_id = ?
              AND u.status = ?
              AND u.measurement_date IS NOT NULL
            GROUP BY u.measurement_date
        ', [$farmId, Upload::STATUS_COMPLETED]);

        $countByDate = [];
        foreach ($pointCountRows as $row) {
            $countByDate[(string) $row->date] = (int) $row->count_points;
        }

        $placeholders = implode(',', array_fill(0, count(self::TARGET_PARAMETERS), '?'));
        $valueRows = DB::select("
            SELECT
                u.measurement_date AS date,
                rv.parameter_name,
                ROUND(AVG(rv.parameter_value), 2) AS avg_value,
                ROUND(MIN(rv.parameter_value), 2) AS min_value,
                ROUND(MAX(rv.parameter_value), 2) AS max_value,
                MAX(rv.unit) AS unit
            FROM uploads u
            INNER JOIN analysis_results ar ON ar.upload_id = u.id
            INNER JOIN result_values rv ON rv.analysis_result_id = ar.id
            WHERE u.farm_id = ?
              AND u.status = ?
              AND u.measurement_date IS NOT NULL
              AND rv.parameter_name IN ({$placeholders})
            GROUP BY u.measurement_date, rv.parameter_name
            ORDER BY u.measurement_date ASC
        ", array_merge([$farmId, Upload::STATUS_COMPLETED], self::TARGET_PARAMETERS));

        $valuesByDate = [];
        foreach ($valueRows as $row) {
            $date = (string) $row->date;
            $valuesByDate[$date][(string) $row->parameter_name] = [
                'avg' => (float) $row->avg_value,
                'min' => (float) $row->min_value,
                'max' => (float) $row->max_value,
                'unit' => $row->unit,
            ];
        }

        ksort($valuesByDate);

        $measurementItems = [];
        $previousCecAvg = null;

        foreach ($valuesByDate as $date => $values) {
            $currentCecAvg = $values['CEC']['avg'] ?? null;
            $delta = $previousCecAvg !== null && $currentCecAvg !== null
                ? round((float) $currentCecAvg - $previousCecAvg, 2)
                : null;

            $measurementItems[] = [
                'type' => 'measurement',
                'date' => $date,
                'count_points' => $countByDate[$date] ?? 0,
                'values' => $this->sortValues($values),
                'delta' => ['CEC' => $delta],
            ];

            if ($currentCecAvg !== null) {
                $previousCecAvg = (float) $currentCecAvg;
            }
        }

        $workLogRows = DB::select('
            SELECT work_date AS date, work_type, title, detail, amount_value, amount_unit
            FROM work_logs
            WHERE farm_id = ?
            ORDER BY work_date ASC, id ASC
        ', [$farmId]);

        $workLogItems = array_map(fn ($row) => [
            'type' => 'work_log',
            'date' => (string) $row->date,
            'work_type' => (string) $row->work_type,
            'title' => $row->title,
            'detail' => $row->detail,
            'amount_value' => $row->amount_value === null ? null : (float) $row->amount_value,
            'amount_unit' => $row->amount_unit,
        ], $workLogRows);

        $items = array_merge($measurementItems, $workLogItems);
        usort($items, function (array $a, array $b): int {
            if ($a['date'] === $b['date']) {
                return $a['type'] === 'measurement' ? -1 : 1;
            }

            return strcmp($b['date'], $a['date']);
        });

        return ['items' => array_values($items)];
    }

    private function sortValues(array $values): array
    {
        $sorted = [];
        foreach (self::TARGET_PARAMETERS as $parameter) {
            if (array_key_exists($parameter, $values)) {
                $sorted[$parameter] = $values[$parameter];
            }
        }

        return $sorted;
    }
}
