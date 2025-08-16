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
        Schema::create('out_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['guest', 'user'])->default('user'); // Type of recipient (guest or user)
            $table->string('title'); // Notification title
            $table->text('description'); // Notification description
            $table->string('image')->nullable(); // Image associated with the notification
            $table->json('user_ids')->nullable(); // JSON to store specific user IDs (nullable)
            $table->json('guest_ids')->nullable(); // JSON to store specific agent IDs (nullable)
            $table->boolean('seen')->default(false); // Active status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('out_notifications');
    }
};
