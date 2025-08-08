<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instructor extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'specialization',
        'experience_years',
        'bio',
        'status',
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'status' => 'string',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get the courses that this instructor teaches.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_instructor');
    }

    /**
     * Scope a query to only include active instructors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include inactive instructors.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}
