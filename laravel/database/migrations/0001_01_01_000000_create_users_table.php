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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // FK制約があるテーブルを先に削除
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // 不要なテーブルを削除（マイグレーションファイルが削除済みのため）
        if (Schema::hasTable('main_data')) {
            Schema::dropIfExists('main_data');
        }
        if (Schema::hasTable('measurements')) {
            Schema::dropIfExists('measurements');
        }
        if (Schema::hasTable('ml_results')) {
            Schema::dropIfExists('ml_results');
        }
        if (Schema::hasTable('csv_data_rows')) {
            Schema::dropIfExists('csv_data_rows');
        }
        if (Schema::hasTable('csv_files')) {
            Schema::dropIfExists('csv_files');
        }
        
        // usersテーブルを削除
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
