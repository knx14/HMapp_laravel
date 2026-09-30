<?php

namespace App\Services\Farms;

use App\Models\Farm;
use Illuminate\Support\Facades\Schema;

/**
 * 圃場の削除（Web・モバイル共通）。測定（論理削除済みを含む）か作業記録があれば非表示にし、無ければ物理削除する。
 */
class FarmDeleter
{
    public const HIDDEN = 'hidden';

    public const DELETED = 'deleted';

    /**
     * @return self::HIDDEN|self::DELETED
     */
    public function delete(Farm $farm): string
    {
        if ($farm->hasMeasurementData() || (Schema::hasTable('work_logs') && $farm->workLogs()->exists())) {
            $farm->hide();

            return self::HIDDEN;
        }

        $farm->delete();

        return self::DELETED;
    }
}
