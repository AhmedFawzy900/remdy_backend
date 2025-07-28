<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'image',
        'description',
        'plants',
        'plans',
        'status',
    ];

    protected $casts = [
        'plants' => 'array',
        'plans' => 'array',
        'status' => 'string',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    public function reviews()
    {
        return $this->hasMany(Review::class, 'element_id')->where('type', 'article')->where('status', 'accepted');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}
