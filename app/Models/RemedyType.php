<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemedyType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
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

    public function remedies()
    {
        return $this->hasMany(Remedy::class, 'remedy_type_id');
    }

    // New: many-to-many relation (kept alongside hasMany for BC)
    public function remediesMany()
    {
        return $this->belongsToMany(Remedy::class, 'remedy_remedy_type');
    }
}
