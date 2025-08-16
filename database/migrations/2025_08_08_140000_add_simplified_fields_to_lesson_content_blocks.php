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
            $table->string('title')->nullable()->after('type');
            $table->text('description')->nullable()->after('title');
            $table->string('image_url')->nullable()->after('description');
            $table->string('video_url')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_content_blocks', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'image_url', 'video_url']);
        });
    }
}; 