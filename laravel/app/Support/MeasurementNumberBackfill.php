<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class MeasurementNumberBackfill
{
    public function run(): int
    {
        $rows = DB::table('uploads')
            ->orderBy('farm_id')
            ->orderBy('measurement_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get([
                'id',
                'farm_id',
                'measurement_date',
                'measurement_parameters',
                'measurement_number',
                'created_at',
            ]);

        $next = [];
        foreach ($rows as $row) {
            $date = $this->dateKey($row->measurement_date);
            if ($date === null) {
                continue;
            }

            $key = $row->farm_id.'|'.$date;
            if (! isset($next[$key])) {
                $next[$key] = 1;
            }
            if ($row->measurement_number !== null) {
                $next[$key] = max($next[$key], ((int) $row->measurement_number) + 1);
            }
        }

        $updated = 0;
        foreach ($rows as $row) {
            if ($row->measurement_number !== null) {
                continue;
            }
            if ($this->isManualEntry($row->measurement_parameters)) {
                continue;
            }

            $date = $this->dateKey($row->measurement_date);
            if ($date === null) {
                continue;
            }

            $key = $row->farm_id.'|'.$date;
            $number = $next[$key] ?? 1;
            DB::table('uploads')->where('id', $row->id)->update([
                'measurement_number' => $number,
            ]);
            $next[$key] = $number + 1;
            $updated++;
        }

        return $updated;
    }

    private function isManualEntry(mixed $parameters): bool
    {
        if (is_string($parameters)) {
            $decoded = json_decode($parameters, true);
            $parameters = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($parameters)) {
            return false;
        }

        $value = $parameters['manual_entry'] ?? false;

        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    private function dateKey(mixed $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        $value = (string) $date;

        return strlen($value) >= 10 ? substr($value, 0, 10) : null;
    }
}
