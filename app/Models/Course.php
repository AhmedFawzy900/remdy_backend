<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'image',
        'title',
        'description',
        'duration',
        'sessionsNumber',
        'price',
        'plan',
        'overview',
        'courseContent',
        'instructors',
        'selectedRemedies',
        'relatedCourses',
        'status',
        'sessions',
    ];

    protected $casts = [
        'courseContent' => 'array',
        'instructors' => 'array',
        'selectedRemedies' => 'array',
        'relatedCourses' => 'array',
        'sessions' => 'array',
        'status' => 'string',
        'price' => 'decimal:2',
        'sessionsNumber' => 'integer',
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
}
