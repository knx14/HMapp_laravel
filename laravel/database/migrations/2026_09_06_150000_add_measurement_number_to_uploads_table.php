<?php

use App\Support\MeasurementNumberBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->unsignedInteger('measurement_number')
                ->nullable()
                ->after('measurement_date');
        });

        (new MeasurementNumberBackfill)->run();

        Schema::table('uploads', function (Blueprint $table) {
            $table->unique(
                ['farm_id', 'measurement_date', 'measurement_number'],
                'uploads_farm_date_measurement_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropUnique('uploads_farm_date_measurement_number_unique');
            $table->dropColumn('measurement_number');
        });
    }
};
