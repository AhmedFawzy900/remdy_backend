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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('videoLink');
            $table->string('title');
            $table->text('description');
            $table->string('visiblePlans')->default('all');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('ingredients')->nullable();
            $table->json('instructions')->nullable();
            $table->json('benefits')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
