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
        Schema::create('body_system_remedy', function (Blueprint $table) {
            $table->unsignedBigInteger('body_system_id');
            $table->unsignedBigInteger('remedy_id');

            $table->primary(['body_system_id', 'remedy_id']);
            $table->foreign('body_system_id')->references('id')->on('body_systems')->onDelete('cascade');
            $table->foreign('remedy_id')->references('id')->on('remedies')->onDelete('cascade');
            $table->index('body_system_id');
            $table->index('remedy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('body_system_remedy');
    }
}; 