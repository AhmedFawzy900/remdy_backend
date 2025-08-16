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
        Schema::create('disease_remedy', function (Blueprint $table) {
            $table->unsignedBigInteger('disease_id');
            $table->unsignedBigInteger('remedy_id');

            $table->primary(['disease_id', 'remedy_id']);
            $table->foreign('disease_id')->references('id')->on('diseases')->onDelete('cascade');
            $table->foreign('remedy_id')->references('id')->on('remedies')->onDelete('cascade');
            $table->index('disease_id');
            $table->index('remedy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disease_remedy');
    }
}; 