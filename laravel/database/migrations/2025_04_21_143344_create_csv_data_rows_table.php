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
        Schema::create('csv_data_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_file_id')->constrained('csv_files')->onDelete('cascade');
            $table->dateTime('date_time');
            $table->integer('total_count');
            $table->string('memo')->nullable();
            $table->integer('count1');
            $table->integer('count2');
            $table->string('command')->nullable();
            $table->text('frequency')->nullable();
            $table->text('c_real')->nullable();
            $table->text('c_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_data_rows');
    }
};
