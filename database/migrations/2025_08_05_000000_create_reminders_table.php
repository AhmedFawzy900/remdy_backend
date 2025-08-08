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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('element_type'); // 'remedy', 'article', 'course', 'video', etc.
            $table->unsignedBigInteger('element_id');
            $table->string('day')->nullable(); // 'monday', 'tuesday', etc. or null for all days
            $table->time('time'); // Time in format HH:MM:SS
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure a user can't have duplicate reminders for the same element at the same time
            $table->unique(['user_id', 'element_type', 'element_id', 'day', 'time'], 'unique_user_reminder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
}; 