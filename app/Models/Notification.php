<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'title',
        'body',
        'type',
        'status',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';
    const TYPE_CUSTOM = 'custom';

    const STATUS_UNREAD = 'unread';
    const STATUS_READ = 'read';
    const STATUS_ARCHIVED = 'archived';

    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_UNREAD);
    }
    public function scopeRead($query)
    {
        return $query->where('status', self::STATUS_READ);
    }
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
} 