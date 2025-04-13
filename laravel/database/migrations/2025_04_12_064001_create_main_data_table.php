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
        Schema::create('main_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ← 追加
            $table->dateTime('date_time');
            $table->integer('total_count');
            $table->string('memo')->nullable();
            $table->integer('count1');
            $table->integer('count2');
            $table->string('command')->nullable();
            $table->text('frequency')->nullable();
            $table->text('c_real')->nullable();
            $table->text('c_imag')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_data');
    }
};
