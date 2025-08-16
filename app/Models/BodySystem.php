<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodySystem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
    ];

    protected $casts = [
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

    // New: many-to-many relation to remedies
    public function remedies()
    {
        return $this->belongsToMany(Remedy::class, 'body_system_remedy');
    }
}
