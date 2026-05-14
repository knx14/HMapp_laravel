<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->string('work_type', 32);
            $table->date('work_date');
            $table->string('title', 128)->nullable();
            $table->text('detail')->nullable();
            $table->decimal('amount_value', 10, 2)->nullable();
            $table->string('amount_unit', 16)->nullable();
            $table->string('scope', 16)->default('whole');
            $table->timestamps();

            $table->index(['farm_id', 'work_date'], 'idx_work_logs_farm_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
