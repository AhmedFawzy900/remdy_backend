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
        Schema::table('reminders', function (Blueprint $table) {
            // Add new JSON column for multiple days only if it doesn't exist
            if (!Schema::hasColumn('reminders', 'days')) {
                $table->json('days')->nullable()->after('element_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            // Drop the new days column only if it exists
            if (Schema::hasColumn('reminders', 'days')) {
                $table->dropColumn('days');
            }
        });
    }
}; 