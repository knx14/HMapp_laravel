<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            // upload_id を 1:1 にするため UNIQUE 付与
            try {
                $table->unique('upload_id', 'analysis_results_upload_id_unique');
            } catch (\Throwable $e) {
            }

            // 緯度経度を NULL 許可かつ精度を (10,7) に変更（doctrine/dbal が必要）
            if (Schema::hasColumn('analysis_results', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->change();
            }
            if (Schema::hasColumn('analysis_results', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            // UNIQUE を解除
            try {
                $table->dropUnique('analysis_results_upload_id_unique');
            } catch (\Throwable $e) {
            }

            // 緯度経度の元の型/NOT NULL に戻す（作成時点の定義に合わせる）
            if (Schema::hasColumn('analysis_results', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable(false)->change();
            }
            if (Schema::hasColumn('analysis_results', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable(false)->change();
            }
        });
    }
};


