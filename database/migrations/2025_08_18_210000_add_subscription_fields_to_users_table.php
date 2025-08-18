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
            $table->enum('subscription_interval', ['monthly', 'yearly'])->nullable()->after('subscription_plan');
            $table->dateTime('subscription_started_at')->nullable()->after('subscription_interval');
            $table->dateTime('subscription_ends_at')->nullable()->after('subscription_started_at');
            $table->dateTime('trial_ends_at')->nullable()->after('subscription_ends_at');
            $table->boolean('has_used_trial')->default(false)->after('trial_ends_at');
            $table->string('last_subscription_reference')->nullable()->after('has_used_trial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_interval',
                'subscription_started_at',
                'subscription_ends_at',
                'trial_ends_at',
                'has_used_trial',
                'last_subscription_reference',
            ]);
        });
    }
};


