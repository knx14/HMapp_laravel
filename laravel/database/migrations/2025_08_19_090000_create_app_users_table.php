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
		Schema::create('app_users', function (Blueprint $table) {
			$table->id();
			$table->string('cognito_sub')->unique();
			$table->string('name')->nullable();
			$table->string('email')->nullable();
			$table->string('ja_name')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('app_users');
	}
};


