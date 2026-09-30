<?php

namespace App\Services\Measurements;

use App\Casts\JstDateTime;
use App\Models\AppUser;
use App\Models\Upload;
use App\Support\SoilParameterUnits;
use Illuminate\Support\Facades\Gate;

/**
 * 測定データ閲覧の詳細ポップアップ（資料 p11、p17）の表示内容。
 */
class MeasurementDetail
{
    /** 資料の詳細画面の並び */
    public const PARAMETERS = ['CEC', 'CaO', 'K2O', 'MgO'];

    /**
     * @return array<string, mixed>
     */
    public function present(Upload $upload, AppUser $user): array
    {
        $upload->loadMissing(['farm.appUser', 'analysisResult.resultValues']);

        $farm = $upload->farm;
        $point = $upload->analysisResult;
        $values = $point?->resultValues->keyBy('parameter_name') ?? collect();
        $trashed = $upload->trashed();
        $estimatedAt = $point?->resultValues->min('created_at');

        return [
            'id' => $upload->id,
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->farm_name,
                'cultivation_method' => $farm->cultivation_method,
                'crop_type' => $farm->crop_type,
                'boundary_polygon' => $farm->boundary_polygon ?? [],
            ],
            'user_name' => $user->isAdmin() ? ($farm->appUser?->name ?? '-') : null,
            'measurement_number' => $upload->measurement_number,
            'measured_at' => $upload->measuredAtLabel(),
            'measurement_date' => $upload->measurement_date?->format('Y-m-d'),
            'measurement_time' => $upload->measured_at?->format('H:i'),
            'manual_entry' => $upload->isManualEntry(),
            'values' => array_map(fn (string $name) => [
                'parameter' => $name,
                'value' => $values->get($name)?->parameter_value,
                'unit' => SoilParameterUnits::unitFor($name),
            ], self::PARAMETERS),
            'estimated_at' => $estimatedAt?->copy()->timezone(JstDateTime::TIMEZONE)->format('Y-m-d H:i'),
            'estimation_model' => '-',
            'location' => $point && $point->latitude !== null && $point->longitude !== null
                ? ['latitude' => $point->latitude, 'longitude' => $point->longitude]
                : null,
            'deleted_at' => $upload->deleted_at?->copy()->timezone(JstDateTime::TIMEZONE)->format('Y-m-d H:i:s'),
            'can' => [
                'move_location' => !$trashed && $point !== null && Gate::forUser($user)->allows('update', $upload),
                'edit' => !$trashed && Gate::forUser($user)->allows('edit', $upload),
            ],
        ];
    }
}
