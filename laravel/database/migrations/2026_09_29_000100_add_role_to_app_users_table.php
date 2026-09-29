<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('ja_name')->index();
            $table->timestamp('admin_granted_at')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'admin_granted_at']);
        });
    }
};
