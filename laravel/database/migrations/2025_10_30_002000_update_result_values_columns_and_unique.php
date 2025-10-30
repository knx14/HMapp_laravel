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
        Schema::table('result_values', function (Blueprint $table) {
            // カラム名変更（doctrine/dbal が必要）
            if (Schema::hasColumn('result_values', 'parameter')) {
                $table->renameColumn('parameter', 'parameter_name');
            }
            if (Schema::hasColumn('result_values', 'value')) {
                $table->renameColumn('value', 'parameter_value');
            }
        });

        Schema::table('result_values', function (Blueprint $table) {
            // 型変更: parameter_value を double に
            if (Schema::hasColumn('result_values', 'parameter_value')) {
                $table->double('parameter_value')->change();
            }

            // 複合UNIQUE (analysis_result_id, parameter_name)
            try {
                $table->unique(['analysis_result_id', 'parameter_name'], 'result_values_analysis_param_unique');
            } catch (\Throwable $e) {
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('result_values', function (Blueprint $table) {
            // UNIQUE 解除
            try {
                $table->dropUnique('result_values_analysis_param_unique');
            } catch (\Throwable $e) {
            }

            // 型を元に戻す
            if (Schema::hasColumn('result_values', 'parameter_value')) {
                $table->float('parameter_value')->change();
            }
        });

        Schema::table('result_values', function (Blueprint $table) {
            // カラム名を元に戻す
            if (Schema::hasColumn('result_values', 'parameter_name')) {
                $table->renameColumn('parameter_name', 'parameter');
            }
            if (Schema::hasColumn('result_values', 'parameter_value')) {
                $table->renameColumn('parameter_value', 'value');
            }
        });
    }
};


