<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'element_id',
        'rate',
        'message',
        'status',
    ];

    const TYPE_REMEDY = 'remedy';
    const TYPE_COURSE = 'course';
    const TYPE_VIDEO = 'video';
    const TYPE_ARTICLE = 'article';
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACTIVE = 'active';

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 