<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'image',
        'videoLink',
        'title',
        'description',
        'visiblePlans',
        'status',
        'ingredients',
        'instructions',
        'benefits',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'instructions' => 'array',
        'benefits' => 'array',
        'status' => 'string',
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
