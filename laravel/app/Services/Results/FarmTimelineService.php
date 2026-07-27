<?php

namespace App\Services\Results;

use App\Models\Upload;
use Illuminate\Support\Facades\DB;

class FarmTimelineService
{
    private const TARGET_PARAMETERS = ['CEC', 'CaO', 'K2O', 'MgO'];

    public function get(int $farmId): array
    {
        // manual_entry は手動登録時に明示的に付与している識別子。
        // file_path は保存先であり、入力元の判定には使用しない。
        $sourceSql = "CASE
            WHEN JSON_UNQUOTE(JSON_EXTRACT(u.measurement_parameters, '$.manual_entry')) = 'true'
                THEN 'manual'
            ELSE 'sensor'
        END";

        $pointCountRows = DB::select("
            SELECT
                u.measurement_date AS date,
                {$sourceSql} AS measurement_source,
                COUNT(DISTINCT ar.id) AS count_points,
                MAX(CASE
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(u.measurement_parameters, '$.manual_entry')) = 'true'
                        THEN u.id
                    ELSE NULL
                END) AS manual_result_upload_id
            FROM uploads u
            INNER JOIN analysis_results ar ON ar.upload_id = u.id
            WHERE u.farm_id = ?
              AND u.status = ?
              AND u.measurement_date IS NOT NULL
            GROUP BY u.measurement_date, measurement_source
        ", [$farmId, Upload::STATUS_COMPLETED]);

        $metadataByDateAndSource = [];
        foreach ($pointCountRows as $row) {
            $date = (string) $row->date;
            $source = (string) $row->measurement_source;
            $metadataByDateAndSource[$date][$source] = [
                'count_points' => (int) $row->count_points,
                'manual_result_upload_id' => $row->manual_result_upload_id === null
                    ? null
                    : (int) $row->manual_result_upload_id,
            ];
        }

        $placeholders = implode(',', array_fill(0, count(self::TARGET_PARAMETERS), '?'));
        $valueRows = DB::select("
            SELECT
                u.measurement_date AS date,
                {$sourceSql} AS measurement_source,
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
            GROUP BY u.measurement_date, measurement_source, rv.parameter_name
            ORDER BY u.measurement_date ASC, measurement_source ASC
        ", array_merge([$farmId, Upload::STATUS_COMPLETED], self::TARGET_PARAMETERS));

        $valuesByDateAndSource = [];
        foreach ($valueRows as $row) {
            $date = (string) $row->date;
            $source = (string) $row->measurement_source;
            $valuesByDateAndSource[$date][$source][(string) $row->parameter_name] = [
                'avg' => (float) $row->avg_value,
                'min' => (float) $row->min_value,
                'max' => (float) $row->max_value,
                'unit' => $row->unit,
            ];
        }

        ksort($valuesByDateAndSource);

        $measurementItems = [];
        $previousCecAvgBySource = [];

        foreach ($valuesByDateAndSource as $date => $valuesBySource) {
            ksort($valuesBySource);

            foreach ($valuesBySource as $source => $values) {
                $currentCecAvg = $values['CEC']['avg'] ?? null;
                $previousCecAvg = $previousCecAvgBySource[$source] ?? null;
                $delta = $previousCecAvg !== null && $currentCecAvg !== null
                    ? round((float) $currentCecAvg - $previousCecAvg, 2)
                    : null;

                $metadata = $metadataByDateAndSource[$date][$source] ?? [];
                $measurementItems[] = [
                    'type' => 'measurement',
                    'date' => $date,
                    'measurement_source' => $source,
                    'manual_result_upload_id' => $metadata['manual_result_upload_id'] ?? null,
                    'count_points' => $metadata['count_points'] ?? 0,
                    'values' => $this->sortValues($values),
                    'delta' => ['CEC' => $delta],
                ];

                if ($currentCecAvg !== null) {
                    $previousCecAvgBySource[$source] = (float) $currentCecAvg;
                }
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
            $dateComparison = strcmp($b['date'], $a['date']);
            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'measurement' ? -1 : 1;
            }

            if ($a['type'] === 'measurement') {
                $sourceRank = ['manual' => 0, 'sensor' => 1];

                return ($sourceRank[$a['measurement_source']] ?? 2)
                    <=> ($sourceRank[$b['measurement_source']] ?? 2);
            }

            return 0;
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
