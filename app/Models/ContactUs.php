<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    protected $table = 'contact_us';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
    ];

    const STATUS_NEW = 'new';
    const STATUS_READ = 'read';
    const STATUS_ARCHIVED = 'archived';

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }
    public function scopeRead($query)
    {
        return $query->where('status', self::STATUS_READ);
    }
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }
} 