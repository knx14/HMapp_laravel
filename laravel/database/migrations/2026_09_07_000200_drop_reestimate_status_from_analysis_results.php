<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analysis_results') && Schema::hasColumn('analysis_results', 'reestimate_status')) {
            Schema::table('analysis_results', function (Blueprint $table) {
                $table->dropColumn('reestimate_status');
            });
        }
    }

    public function down(): void
    {
        // 再推定は対象外のため、列は復元しない。
    }
};
