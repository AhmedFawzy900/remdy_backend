<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LessonContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'type',
        'title',
        'description',
        'image_url',
        'video_url',
        'content',
        'order',
        'is_active',
        'remedy_id',
    ];

    protected $casts = [
        'content' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
        'remedy_id' => 'integer',
    ];

    protected $attributes = [
        'content' => '{}',
        'is_active' => true,
    ];

    // Common content types for reference (but not enforced)
    const TYPE_VIDEO = 'video';
    const TYPE_IMAGE = 'image';
    const TYPE_TEXT = 'text';
    const TYPE_REMEDY = 'remedy';
    const TYPE_INGREDIENTS = 'ingredients';
    const TYPE_TIPS = 'tips';
    const TYPE_ACTIVITIES = 'activities';
    const TYPE_WHATS_INCLUDED = 'whats_included';
    const TYPE_INSTRUCTIONS = 'instructions';

    /**
     * Get the lesson that owns the content block.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the remedy if this block is of type 'remedy'.
     */
    public function remedy(): BelongsTo
    {
        return $this->belongsTo(Remedy::class);
    }

    /**
     * Scope to get only active blocks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order blocks by their order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get content by key with fallback
     */
    public function getContent($key, $default = null)
    {
        return data_get($this->content, $key, $default);
    }

    /**
     * Set content by key
     */
    public function setContent($key, $value)
    {
        $content = $this->content;
        data_set($content, $key, $value);
        $this->content = $content;
        return $this;
    }
} 