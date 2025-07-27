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
        Schema::create('remedies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('main_image_url')->nullable();
            $table->string('disease');
            $table->foreignId('remedy_type_id')->constrained('remedy_types')->onDelete('cascade');
            $table->foreignId('body_system_id')->constrained('body_systems')->onDelete('cascade');
            $table->text('description');
            $table->string('visible_to_plan')->default('all');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('ingredients')->nullable();
            $table->json('instructions')->nullable();
            $table->json('benefits')->nullable();
            $table->json('precautions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remedies');
    }
};
