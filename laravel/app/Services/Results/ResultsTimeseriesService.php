<?php

namespace App\Services\Results;

use App\Models\Upload;
use Illuminate\Support\Facades\DB;

class ResultsTimeseriesService
{
    private const ALLOWED_PARAMETERS = ['CEC', 'CaO', 'K2O', 'MgO'];

    public function allowedParameters(): array
    {
        return self::ALLOWED_PARAMETERS;
    }

    public function get(int $farmId, string $parameter = 'CEC'): array
    {
        $rows = DB::select('
            SELECT
                u.measurement_date AS date,
                ROUND(AVG(rv.parameter_value), 2) AS avg_value,
                ROUND(MIN(rv.parameter_value), 2) AS min_value,
                ROUND(MAX(rv.parameter_value), 2) AS max_value,
                COUNT(DISTINCT ar.id) AS count_points,
                MAX(rv.unit) AS unit
            FROM uploads u
            INNER JOIN analysis_results ar ON ar.upload_id = u.id
            LEFT JOIN result_values rv
                ON rv.analysis_result_id = ar.id
                AND rv.parameter_name = ?
            WHERE u.farm_id = ?
              AND u.status = ?
              AND u.measurement_date IS NOT NULL
            GROUP BY u.measurement_date
            HAVING COUNT(rv.id) > 0
            ORDER BY u.measurement_date ASC
        ', [$parameter, $farmId, Upload::STATUS_COMPLETED]);

        $unit = $rows !== [] ? $rows[0]->unit : null;

        $points = array_map(fn ($row) => [
            'date' => (string) $row->date,
            'avg' => (float) $row->avg_value,
            'min' => (float) $row->min_value,
            'max' => (float) $row->max_value,
            'count' => (int) $row->count_points,
        ], $rows);

        $workLogs = DB::select('
            SELECT work_date AS date, work_type, title
            FROM work_logs
            WHERE farm_id = ?
            ORDER BY work_date ASC, id ASC
        ', [$farmId]);

        return [
            'parameter' => $parameter,
            'unit' => $unit,
            'points' => $points,
            'work_logs' => array_map(fn ($row) => [
                'date' => (string) $row->date,
                'work_type' => (string) $row->work_type,
                'title' => $row->title,
            ], $workLogs),
        ];
    }
}
