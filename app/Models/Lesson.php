<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
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

    /**
     * Get the content blocks for the lesson.
     */
    public function contentBlocks(): HasMany
    {
        return $this->hasMany(LessonContentBlock::class)->ordered();
    }

    /**
     * Get the active content blocks for the lesson.
     */
    public function activeContentBlocks(): HasMany
    {
        return $this->hasMany(LessonContentBlock::class)->active()->ordered();
    }

    /**
     * Get content blocks by type
     */
    public function contentBlocksByType($type): HasMany
    {
        return $this->hasMany(LessonContentBlock::class)->ofType($type)->ordered();
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
        return $query->orderBy('created_at', 'asc');
    }
}
