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
		Schema::create('result_values', function (Blueprint $table) {
			$table->id();
			$table->foreignId('analysis_result_id')->constrained('analysis_results')->cascadeOnDelete();
			$table->string('parameter');
			$table->float('value');
			$table->string('unit');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('result_values');
	}
};


