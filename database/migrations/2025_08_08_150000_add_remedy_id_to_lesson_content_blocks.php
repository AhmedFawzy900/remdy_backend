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
        Schema::table('lesson_content_blocks', function (Blueprint $table) {
            $table->foreignId('remedy_id')->nullable()->after('video_url')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_content_blocks', function (Blueprint $table) {
            $table->dropForeign(['remedy_id']);
            $table->dropColumn('remedy_id');
        });
    }
}; 