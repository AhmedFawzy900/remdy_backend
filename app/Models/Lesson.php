<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'image',
        'whats_included',
        'activities',
        'video',
        'instructions',
        'ingredients',
        'tips',
        'status',
        'order',
    ];

    protected $casts = [
        'whats_included' => 'array',
        'activities' => 'array',
        'video' => 'array',
        'instructions' => 'array',
        'ingredients' => 'array',
        'tips' => 'array',
        'status' => 'string',
        'order' => 'integer',
    ];

    protected $attributes = [
        'whats_included' => '[]',
        'activities' => '{}',
        'video' => '{}',
        'instructions' => '[]',
        'ingredients' => '[]',
        'tips' => '{}',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get the course that owns the lesson.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
