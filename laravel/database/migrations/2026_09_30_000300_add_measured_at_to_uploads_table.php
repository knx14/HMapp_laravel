<?php

use App\Support\MeasuredAtBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            // 日本時間の壁時計時刻（App\Casts\JstDateTime）
            $table->dateTime('measured_at')->nullable()->after('measurement_date');
        });

        (new MeasuredAtBackfill)->run();
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('measured_at');
        });
    }
};
