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
        Schema::create('feedback', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
			$table->string('rating'); // 1..5
			$table->text('message')->nullable();
			$table->string('device')->nullable();
			$table->string('app_version')->nullable();
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
