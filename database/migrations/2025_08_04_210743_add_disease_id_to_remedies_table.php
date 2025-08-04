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
        Schema::table('remedies', function (Blueprint $table) {
            $table->foreignId('disease_id')->nullable()->after('disease')->constrained('diseases')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remedies', function (Blueprint $table) {
            $table->dropForeign(['disease_id']);
            $table->dropColumn('disease_id');
        });
    }
};
