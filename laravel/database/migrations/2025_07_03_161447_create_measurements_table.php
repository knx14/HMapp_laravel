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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->string('cognito_id', 255)->nullable(); // JSONのuserIdに対応、nullableに設定
            $table->string('s3_file_path', 2048)->nullable(); // S3パス、nullableに設定
            $table->string('location_info', 255)->nullable(); // 位置情報、nullableに設定
            $table->string('serial_no', 255)->nullable(); // シリアルNo、nullableに設定
            $table->decimal('predicted_cec', 10, 5)->nullable(); // 演算結果、nullableに設定
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
