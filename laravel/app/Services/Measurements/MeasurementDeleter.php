<?php

namespace App\Services\Measurements;

use App\Models\AppUser;
use App\Models\Upload;
use Illuminate\Support\Facades\Gate;

/**
 * 測定の削除は Web・モバイルとも論理削除。analysis_results / result_values / S3 の生データは残す。
 */
class MeasurementDeleter
{
    public function delete(Upload $upload): void
    {
        $upload->delete();
    }

    /**
     * 権限のある、削除されていない測定だけを消す。
     *
     * @param  array<int>  $uploadIds
     * @return int 削除した件数
     */
    public function deleteMany(AppUser $user, array $uploadIds): int
    {
        $deleted = 0;

        Upload::query()
            ->with('farm')
            ->whereIn('id', $uploadIds)
            ->get()
            ->each(function (Upload $upload) use ($user, &$deleted): void {
                if (Gate::forUser($user)->denies('view', $upload) || Gate::forUser($user)->denies('delete', $upload)) {
                    return;
                }

                $this->delete($upload);
                $deleted++;
            });

        return $deleted;
    }
}
