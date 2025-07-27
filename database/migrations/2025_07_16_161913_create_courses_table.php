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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('duration')->nullable();
            $table->integer('sessionsNumber')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('plan')->nullable();
            $table->text('overview')->nullable();
            $table->json('courseContent')->nullable();
            $table->json('instructors')->nullable();
            $table->json('selectedRemedies')->nullable();
            $table->json('relatedCourses')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('sessions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
