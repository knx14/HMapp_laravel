<?php

namespace App\Services\Measurements;

use App\Casts\JstDateTime;
use App\Models\ResultValue;
use App\Models\Upload;
use App\Support\SoilParameterUnits;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 管理者による測定の編集（推定値・測定日時・測定番号）。圃場の付け替えは扱わない。
 */
class MeasurementEditor
{
    /**
     * @param  array{measurement_date: string, measurement_time?: ?string, measurement_number?: ?int, values?: array<string, mixed>}  $input
     */
    public function update(Upload $upload, array $input): void
    {
        $date = $input['measurement_date'];
        $time = $input['measurement_time'] ?? null;
        $number = isset($input['measurement_number']) ? (int) $input['measurement_number'] : null;
        $values = $input['values'] ?? [];

        if ($number !== null && $this->numberTaken($upload, $date, $number)) {
            throw ValidationException::withMessages([
                'measurement_number' => 'この圃場・測定日には同じ測定番号が既にあります（削除済みの測定を含む）。',
            ]);
        }

        $point = $upload->analysisResult;
        if ($point === null && array_filter($values, fn ($value) => $value !== null) !== []) {
            throw ValidationException::withMessages([
                'values' => '推定結果の無い測定のため、推定値を保存できません。',
            ]);
        }

        DB::transaction(function () use ($upload, $date, $time, $number, $values, $point): void {
            $upload->measurement_date = $date;
            $upload->measured_at = $time === null
                ? null
                : CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} {$time}", JstDateTime::TIMEZONE)->startOfMinute();
            $upload->measurement_number = $number;
            $upload->save();

            if ($point === null) {
                return;
            }

            foreach (MeasurementDetail::PARAMETERS as $name) {
                if (!array_key_exists($name, $values)) {
                    continue;
                }

                if ($values[$name] === null) {
                    $point->resultValues()->where('parameter_name', $name)->delete();

                    continue;
                }

                ResultValue::query()->updateOrCreate(
                    ['analysis_result_id' => $point->id, 'parameter_name' => $name],
                    ['parameter_value' => (float) $values[$name], 'unit' => SoilParameterUnits::unitFor($name)],
                );
            }
        });
    }

    private function numberTaken(Upload $upload, string $date, int $number): bool
    {
        return Upload::withTrashed()
            ->where('farm_id', $upload->farm_id)
            ->whereDate('measurement_date', $date)
            ->where('measurement_number', $number)
            ->whereKeyNot($upload->id)
            ->exists();
    }
}
