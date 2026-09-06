<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 推定モデル記録と再推定用列は対象外になったため、何も追加しない。
    }

    public function down(): void
    {
    }
};
