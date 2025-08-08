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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('image')->nullable();
            $table->json('whats_included')->nullable(); // Array of image and title
            $table->json('activities')->nullable(); // Title and array of JSON with titles
            $table->json('video')->nullable(); // Title, description, video link
            $table->json('instructions')->nullable(); // Title and image
            $table->json('ingredients')->nullable(); // Title and image
            $table->json('tips')->nullable(); // Title, description, array of content with title and image
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('order')->default(0); // For ordering lessons within a course
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
