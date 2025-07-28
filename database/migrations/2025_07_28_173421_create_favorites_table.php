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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('favoritable_type'); // App\Models\Remedy, App\Models\Article, etc.
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamps();

            // Ensure a user can't favorite the same item twice
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'unique_user_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
