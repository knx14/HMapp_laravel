<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('farms')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE farms MODIFY cultivation_method VARCHAR(255) NULL');
            DB::statement('ALTER TABLE farms MODIFY crop_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE farms MODIFY boundary_polygon JSON NULL');
        }
    }

    public function down(): void
    {
        // 既存データにNULLが入っている状態でNOT NULLへ戻すと失敗するため省略
    }
};
