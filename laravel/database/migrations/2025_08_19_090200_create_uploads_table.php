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
		Schema::create('uploads', function (Blueprint $table) {
			$table->id();
			$table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
			$table->string('file_path');
			$table->date('measurement_date');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('uploads');
	}
};


