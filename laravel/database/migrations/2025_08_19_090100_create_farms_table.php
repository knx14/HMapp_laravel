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
		Schema::create('farms', function (Blueprint $table) {
			$table->id();
			$table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
			$table->string('farm_name');
			$table->string('cultivation_method')->nullable();
			$table->string('crop_type')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('farms');
	}
};


