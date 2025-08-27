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
        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('plan', ['rookie', 'skilled', 'master']);
            $table->enum('interval', ['monthly', 'yearly'])->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('reference')->nullable(); // Payment reference
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->enum('action', ['activated', 'renewed', 'cancelled', 'expired']); // What action triggered this record
            $table->decimal('amount_paid', 10, 2)->nullable(); // Amount paid for this subscription
            $table->string('payment_method')->nullable(); // Payment method used
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();

            // Index for better performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_histories');
    }
};