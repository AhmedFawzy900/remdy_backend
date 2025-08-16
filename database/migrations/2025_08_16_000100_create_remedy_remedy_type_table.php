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
        Schema::create('remedy_remedy_type', function (Blueprint $table) {
            $table->unsignedBigInteger('remedy_id');
            $table->unsignedBigInteger('remedy_type_id');

            $table->primary(['remedy_id', 'remedy_type_id']);
            $table->foreign('remedy_id')->references('id')->on('remedies')->onDelete('cascade');
            $table->foreign('remedy_type_id')->references('id')->on('remedy_types')->onDelete('cascade');
            $table->index('remedy_id');
            $table->index('remedy_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remedy_remedy_type');
    }
}; 