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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->enum('account_verification', ['yes', 'no'])->default('no')->after('account_status');
            $table->string('otp')->nullable()->after('account_verification');
            $table->string('otp_source')->nullable()->after('otp');
            $table->date('otp_expired_date')->nullable()->after('otp_source');
            $table->enum('code_usage', ['done', null])->nullable()->after('otp_expired_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'account_verification',
                'otp',
                'otp_source',
                'otp_expired_date',
                'code_usage'
            ]);
        });
    }
}; 