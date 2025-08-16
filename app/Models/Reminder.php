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
        'days',
        'day', // Keep for backward compatibility during transition
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
        'days' => 'array',
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
        return $query->where(function ($q) use ($day) {
            $q->whereJsonContains('days', $day)
              ->orWhere('day', $day);
        });
    }

    /**
     * Scope a query to only include reminders for all days.
     */
    public function scopeForAllDays($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('days')
              ->orWhereNull('day');
        });
    }

    /**
     * Scope a query to only include reminders that have any of the specified days.
     */
    public function scopeForAnyDay($query, $days)
    {
        if (is_array($days)) {
            return $query->where(function ($q) use ($days) {
                foreach ($days as $day) {
                    $q->orWhereJsonContains('days', $day)
                      ->orWhere('day', $day);
                }
            });
        }
        return $query->where(function ($q) use ($days) {
            $q->whereJsonContains('days', $days)
              ->orWhere('day', $days);
        });
    }

    /**
     * Get the formatted time for display.
     */
    public function getFormattedTimeAttribute()
    {
        return $this->time->format('h:i A');
    }

    /**
     * Get the day names for display.
     */
    public function getDayNamesAttribute()
    {
        // Handle both new 'days' column and old 'day' column during transition
        if ($this->days) {
            if (empty($this->days)) {
                return 'All Days';
            }
            $dayNames = array_map('ucfirst', $this->days);
            return implode(', ', $dayNames);
        }
        
        // Fallback to old 'day' column if 'days' is not set
        if ($this->day) {
            return ucfirst($this->day);
        }
        
        return 'All Days';
    }

    /**
     * Get the day name for display (backward compatibility).
     */
    public function getDayNameAttribute()
    {
        return $this->day_names;
    }
} 