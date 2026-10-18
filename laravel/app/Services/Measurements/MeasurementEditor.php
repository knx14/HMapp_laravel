<?php

namespace App\Services\Measurements;

use App\Casts\JstDateTime;
use App\Models\ResultValue;
use App\Models\Upload;
use App\Support\SoilParameterUnits;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * 管理者による測定の編集。推定値・測定日時・測定番号に加え、
 * 測定が属する圃場の所有者・圃場名・栽培方法・作物種別を更新する。
 */
class MeasurementEditor
{
    public function __construct(private MeasurementDetail $detail) {}

    /**
     * @param  array{measurement_date: string, measurement_time?: ?string, measurement_number?: ?int, values?: array<string, mixed>, app_user_id: int, farm_name: string, cultivation_method?: ?string, crop_type?: ?string}  $input
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

        $farm = $upload->farm;
        if ($farm === null) {
            throw ValidationException::withMessages([
                'farm_name' => 'この測定の圃場が見つからないため、保存できません。',
            ]);
        }

        $ownerId = (int) $input['app_user_id'];
        if (! $this->detail->ownerChoices($farm)->contains(fn ($member) => (int) $member->id === $ownerId)) {
            throw ValidationException::withMessages([
                'app_user_id' => 'ユーザー名は、同じ所属の人から選択してください。',
            ]);
        }

        DB::transaction(function () use ($upload, $date, $time, $number, $values, $point, $farm, $input, $ownerId): void {
            $upload->measurement_date = $date;
            $upload->measured_at = $time === null
                ? null
                : CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} {$time}", JstDateTime::TIMEZONE)->startOfMinute();
            $upload->measurement_number = $number;
            $upload->save();

            if ((int) $farm->app_user_id !== $ownerId) {
                Log::info('farm owner changed', [
                    'farm_id' => $farm->id,
                    'from_app_user_id' => $farm->app_user_id,
                    'to_app_user_id' => $ownerId,
                    'by_upload_id' => $upload->id,
                ]);
                $farm->app_user_id = $ownerId;
            }

            $farm->farm_name = $input['farm_name'];
            $farm->cultivation_method = $this->blankToNull($input['cultivation_method'] ?? null);
            $farm->crop_type = $this->blankToNull($input['crop_type'] ?? null);
            $farm->save();

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

    private function blankToNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
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
