<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan',
        'interval',
        'started_at',
        'ends_at',
        'reference',
        'status',
        'action',
        'amount_paid',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the user who owns this subscription history
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this subscription is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->ends_at && 
               now()->lt($this->ends_at);
    }

    /**
     * Scope to get active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('ends_at', '>', now());
    }

    /**
     * Scope to get subscriptions by plan
     */
    public function scopeByPlan($query, $plan)
    {
        return $query->where('plan', $plan);
    }

    /**
     * Scope to get user's subscription history
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}