<?php

use App\Support\OrganizationBackfill;
use App\Support\OrganizationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->string('organization', OrganizationName::MAX_LENGTH)->nullable()->after('ja_name');
            $table->index('organization');
        });

        (new OrganizationBackfill)->run();
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropIndex(['organization']);
            $table->dropColumn('organization');
        });
    }
};
