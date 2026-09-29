<?php

use App\Support\SoilParameterUnits;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (SoilParameterUnits::map() as $parameterName => $unit) {
            DB::table('result_values')
                ->where('parameter_name', $parameterName)
                ->update(['unit' => $unit]);
        }
    }

    public function down(): void
    {
        // 単位表記の変更は可逆でないため、値は戻さない。
    }
};
