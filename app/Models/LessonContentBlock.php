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
        'pdf_url',
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

    // Content types as specified by the user
    const TYPE_CONTENT = 'content';      // List of (image + title)
    const TYPE_TEXT = 'text';           // HTML text (rich text editor)
    const TYPE_VIDEO = 'video';         // Video link + title
    const TYPE_REMEDY = 'remedy';       // Remedy model
    const TYPE_TIP = 'tip';             // Image + text (rich text)
    const TYPE_IMAGE = 'image';         // Image + URL (optional)
    const TYPE_PDF = 'pdf';             // PDF file link + title

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

    /**
     * Get all available content block types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_CONTENT,
            self::TYPE_TEXT,
            self::TYPE_VIDEO,
            self::TYPE_REMEDY,
            self::TYPE_TIP,
            self::TYPE_IMAGE,
            self::TYPE_PDF,
        ];
    }
} 