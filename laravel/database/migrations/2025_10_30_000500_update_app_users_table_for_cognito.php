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
        Schema::table('app_users', function (Blueprint $table) {
            // email の UNIQUE を解除（既存のインデックス名に依存）
            if (Schema::hasColumn('app_users', 'email')) {
                // 既存でユニーク制約がある場合のみドロップ
                try {
                    $table->dropUnique('app_users_email_unique');
                } catch (\Throwable $e) {
                    // インデックス未存在の場合は無視
                }
            }

            // cognito_sub を追加（ユニーク）
            if (!Schema::hasColumn('app_users', 'cognito_sub')) {
                $table->string('cognito_sub')->unique()->after('id');
            }
        });

        Schema::table('app_users', function (Blueprint $table) {
            // 既存カラムの属性変更（change には doctrine/dbal が必要）
            if (Schema::hasColumn('app_users', 'email')) {
                $table->string('email')->nullable()->change();
            }
            if (Schema::hasColumn('app_users', 'name')) {
                $table->string('name')->nullable()->change();
            }

            // password / remember_token を削除
            if (Schema::hasColumn('app_users', 'password')) {
                $table->dropColumn('password');
            }
            if (Schema::hasColumn('app_users', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            // 削除したカラムを復元
            if (!Schema::hasColumn('app_users', 'password')) {
                $table->string('password')->after('email');
            }
            if (!Schema::hasColumn('app_users', 'remember_token')) {
                $table->rememberToken();
            }

            // email を NOT NULL に戻し、UNIQUE を復元
            if (Schema::hasColumn('app_users', 'email')) {
                $table->string('email')->nullable(false)->change();
                try {
                    $table->unique('email', 'app_users_email_unique');
                } catch (\Throwable $e) {
                    // 既に存在する場合は無視
                }
            }

            // name を NOT NULL に戻す
            if (Schema::hasColumn('app_users', 'name')) {
                $table->string('name')->nullable(false)->change();
            }

            // cognito_sub を削除
            if (Schema::hasColumn('app_users', 'cognito_sub')) {
                // 先にユニークインデックスを削除
                try {
                    $table->dropUnique('app_users_cognito_sub_unique');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('cognito_sub');
            }
        });
    }
};


