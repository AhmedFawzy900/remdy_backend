<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'element_type',
        'element_id',
        'day',
        'time',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'time' => 'datetime:H:i:s',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the element that the reminder is for.
     */
    public function element()
    {
        return $this->morphTo('element', 'element_type', 'element_id');
    }

    /**
     * Scope a query to only include active reminders.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include reminders for a specific day.
     */
    public function scopeForDay($query, $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Scope a query to only include reminders for all days.
     */
    public function scopeForAllDays($query)
    {
        return $query->whereNull('day');
    }

    /**
     * Get the formatted time for display.
     */
    public function getFormattedTimeAttribute()
    {
        return $this->time->format('h:i A');
    }

    /**
     * Get the day name for display.
     */
    public function getDayNameAttribute()
    {
        if (!$this->day) {
            return 'All Days';
        }
        
        return ucfirst($this->day);
    }
} 