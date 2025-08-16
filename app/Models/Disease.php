<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    protected $fillable = [
        'name',
        'image',
        'description',
        'status',
        'symptoms',
    ];

    protected $casts = [
        'symptoms' => 'array',
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
        return $this->belongsToMany(Remedy::class, 'disease_remedy');
    }
}
