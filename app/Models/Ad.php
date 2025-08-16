<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    protected $fillable = [
        'title',
        'image',
        'url',
        'status',
        'type',
        'element_id',
    ];

    protected $casts = [
        'status' => 'string',
        'type' => 'string',
        'element_id' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const TYPE_HOME = 'home';
    const TYPE_REMEDY = 'remedy';
    const TYPE_VIDEO = 'video';
    const TYPE_COURSE = 'course';

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeForPlacement($query, string $type, ?int $elementId = null)
    {
        $query->where('type', $type);
        if ($elementId !== null) {
            $query->where('element_id', $elementId);
        }
        return $query;
    }
}
