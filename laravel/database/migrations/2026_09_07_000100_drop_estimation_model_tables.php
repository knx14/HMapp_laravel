<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analysis_results') && Schema::hasColumn('analysis_results', 'estimation_model_id')) {
            try {
                Schema::table('analysis_results', function (Blueprint $table) {
                    $table->dropForeign(['estimation_model_id']);
                });
            } catch (\Throwable) {
                // 外部キーが無い環境（未適用や SQLite）では列削除だけ行う。
            }
        }

        if (Schema::hasTable('analysis_results')) {
            Schema::table('analysis_results', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('analysis_results', 'estimation_model_id') ? 'estimation_model_id' : null,
                    Schema::hasColumn('analysis_results', 'estimation_model_code') ? 'estimation_model_code' : null,
                    Schema::hasColumn('analysis_results', 'estimation_model_version') ? 'estimation_model_version' : null,
                    Schema::hasColumn('analysis_results', 'estimated_at') ? 'estimated_at' : null,
                    Schema::hasColumn('analysis_results', 'reestimate_status') ? 'reestimate_status' : null,
                ]));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('app_user_estimation_models');
        Schema::dropIfExists('estimation_model_items');
        Schema::dropIfExists('estimation_models');
        Schema::dropIfExists('estimation_formulas');
    }

    public function down(): void
    {
        // 推定モデル用テーブルは今回の対象から外すため、復元しない。
    }
};
