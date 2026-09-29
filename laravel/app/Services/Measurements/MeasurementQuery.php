<?php

namespace App\Services\Measurements;

use App\Models\AppUser;
use App\Models\Farm;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Builder;

/**
 * 測定データ閲覧の一覧と CSV で共通の対象行。1行 = uploads 1行。
 */
class MeasurementQuery
{
    /**
     * @param  array<int>|null  $uploadIds  指定したときは、この ID のうち条件に合うものだけに絞る
     */
    public function build(AppUser $user, MeasurementFilters $filters, ?array $uploadIds = null): Builder
    {
        $query = Upload::query()
            ->join('farms', 'farms.id', '=', 'uploads.farm_id')
            ->leftJoin('app_users', 'app_users.id', '=', 'farms.app_user_id')
            ->select([
                'uploads.id',
                'uploads.farm_id',
                'uploads.file_path',
                'uploads.measurement_date',
                'uploads.measurement_number',
                'uploads.measurement_parameters',
                'uploads.deleted_at',
                'farms.farm_name',
                'farms.cultivation_method',
                'farms.crop_type',
                'app_users.name as user_name',
            ])
            ->where('uploads.status', Upload::STATUS_COMPLETED)
            ->whereIn('uploads.farm_id', Farm::query()->accessibleBy($user)->select('farms.id'));

        $this->applyTrashed($query, $user->isAdmin() ? $filters->trashed : MeasurementFilters::TRASHED_WITHOUT);

        if ($uploadIds !== null) {
            $query->whereIn('uploads.id', $uploadIds);
        }

        $this->whereLike($query, 'farms.farm_name', $filters->farmName);
        $this->whereLike($query, 'farms.cultivation_method', $filters->cultivationMethod);
        $this->whereLike($query, 'farms.crop_type', $filters->cropType);

        if ($user->isAdmin()) {
            $this->whereLike($query, 'app_users.name', $filters->userName);
        }

        return $query
            ->orderByDesc('uploads.measurement_date')
            ->orderBy('farms.farm_name')
            ->orderByRaw('uploads.measurement_number IS NULL')
            ->orderBy('uploads.measurement_number')
            ->orderBy('uploads.id');
    }

    private function applyTrashed(Builder $query, string $trashed): void
    {
        match ($trashed) {
            MeasurementFilters::TRASHED_WITH => $query->withTrashed(),
            MeasurementFilters::TRASHED_ONLY => $query->onlyTrashed(),
            default => null,
        };
    }

    private function whereLike(Builder $query, string $column, ?string $value): void
    {
        if ($value === null) {
            return;
        }

        $escaped = addcslashes($value, '\\%_');
        $query->where($column, 'like', '%'.$escaped.'%');
    }
}
