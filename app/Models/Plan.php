<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'price_yearly',
        'trial_days',
        'description',
        'features',
        'badge_emoji',
        'status',
        'order',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
} 