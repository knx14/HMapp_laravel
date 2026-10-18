<?php

namespace App\Services\Measurements;

use App\Casts\JstDateTime;
use App\Models\AppUser;
use App\Models\Farm;
use App\Models\Upload;
use App\Support\OrganizationName;
use App\Support\SoilParameterUnits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
            'owner_id' => $user->isAdmin() ? $farm->app_user_id : null,
            'owner_options' => $user->isAdmin() && ! $trashed
                ? $this->ownerChoices($farm)->map(fn (AppUser $member) => [
                    'id' => $member->id,
                    'label' => $this->ownerLabel($member),
                ])->values()->all()
                : [],
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

    /**
     * 測定の所有者と同じ所属のユーザー。所属が無いときは現在の所有者だけ。
     * 削除済みのユーザーは、今の所有者であるときだけ含める。
     *
     * @return Collection<int, AppUser>
     */
    public function ownerChoices(Farm $farm): Collection
    {
        $farm->loadMissing('appUser');
        $organization = OrganizationName::normalize($farm->appUser?->organization);

        if ($organization === null) {
            return AppUser::withTrashed()
                ->whereKey($farm->app_user_id)
                ->get(['id', 'name', 'email']);
        }

        return AppUser::withTrashed()
            ->where(function (Builder $query) use ($farm, $organization): void {
                $query->where(function (Builder $active) use ($organization): void {
                    $active->whereNull('deleted_at')->where('organization', $organization);
                })->orWhere('app_users.id', $farm->app_user_id);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);
    }

    private function ownerLabel(AppUser $member): string
    {
        $name = $member->name !== null && $member->name !== '' ? $member->name : '（名前なし）';

        return $member->email ? $name.'（'.$member->email.'）' : $name;
    }
}
