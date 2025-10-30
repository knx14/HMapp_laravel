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
        Schema::table('uploads', function (Blueprint $table) {
            // file_path をユニークにする（既存インデックス名に依存）
            try {
                $table->unique('file_path', 'uploads_file_path_unique');
            } catch (\Throwable $e) {
            }

            // measurement_date を NULL 許可に変更（doctrine/dbal が必要）
            if (Schema::hasColumn('uploads', 'measurement_date')) {
                $table->date('measurement_date')->nullable()->change();
            }

            // 付加情報を追加
            if (!Schema::hasColumn('uploads', 'measurement_parameters')) {
                $table->json('measurement_parameters')->nullable()->after('measurement_date');
            }
            if (!Schema::hasColumn('uploads', 'note1')) {
                $table->string('note1')->nullable()->after('measurement_parameters');
            }
            if (!Schema::hasColumn('uploads', 'note2')) {
                $table->string('note2')->nullable()->after('note1');
            }
            if (!Schema::hasColumn('uploads', 'cultivation_type')) {
                $table->string('cultivation_type')->nullable()->after('note2');
            }

            // ステータス列（enum 4種）
            if (!Schema::hasColumn('uploads', 'status')) {
                $table->enum('status', ['uploaded', 'processing', 'completed', 'exec_error'])
                    ->default('uploaded')
                    ->after('cultivation_type');
            }

            // インデックス
            $table->index('farm_id');
            $table->index('measurement_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            // インデックスの削除
            try { $table->dropIndex(['farm_id']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['measurement_date']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['status']); } catch (\Throwable $e) {}

            // ステータス列の削除
            if (Schema::hasColumn('uploads', 'status')) {
                $table->dropColumn('status');
            }

            // 追加した付加情報の削除
            foreach (['cultivation_type', 'note2', 'note1', 'measurement_parameters'] as $col) {
                if (Schema::hasColumn('uploads', $col)) {
                    $table->dropColumn($col);
                }
            }

            // file_path のユニーク解除
            try {
                $table->dropUnique('uploads_file_path_unique');
            } catch (\Throwable $e) {
            }

            // measurement_date を NOT NULL に戻す
            if (Schema::hasColumn('uploads', 'measurement_date')) {
                $table->date('measurement_date')->nullable(false)->change();
            }
        });
    }
};


