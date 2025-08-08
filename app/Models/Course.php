<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'selectedRemedies',
        'relatedCourses',
        'status',
        'sessions',
    ];

    protected $casts = [
        'courseContent' => 'array',
        'selectedRemedies' => 'array',
        'relatedCourses' => 'array',
        'sessions' => 'array',
        'status' => 'string',
        'price' => 'decimal:2',
        'sessionsNumber' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get the instructors for this course.
     */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'course_instructor');
    }

    /**
     * Get the lessons for this course.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->ordered();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'element_id')->where('type', 'course')->where('status', 'accepted');
    }

    /**
     * Get remedies for this course through selectedRemedies field
     */
    public function remedies()
    {
        $remedyIds = $this->selectedRemedies ?? [];
        return Remedy::whereIn('id', $remedyIds);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Get automatically generated related courses based on similar criteria
     */
    public function getRelatedCoursesAttribute()
    {
        // Get instructor IDs for this course
        $instructorIds = $this->instructors()->pluck('instructors.id')->toArray();
        
        // Find related courses based on multiple criteria
        $relatedCourses = Course::where('id', '!=', $this->id)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function($query) use ($instructorIds) {
                // Same plan
                $query->where('plan', $this->plan)
                    // Similar title (using LIKE for partial matches)
                    ->orWhere('title', 'like', '%' . substr($this->title, 0, 3) . '%')
                    ->orWhere('title', 'like', '%' . substr($this->title, -3) . '%')
                    // Same instructors
                    ->orWhereHas('instructors', function($q) use ($instructorIds) {
                        $q->whereIn('instructors.id', $instructorIds);
                    });
            })
            ->with(['reviews.user', 'instructors'])
            ->limit(5)
            ->get();
        
        // If we don't have enough related courses, add some based on plan only
        if ($relatedCourses->count() < 3) {
            $additionalCourses = Course::where('id', '!=', $this->id)
                ->where('status', self::STATUS_ACTIVE)
                ->where('plan', $this->plan)
                ->whereNotIn('id', $relatedCourses->pluck('id'))
                ->with(['reviews.user', 'instructors'])
                ->limit(5 - $relatedCourses->count())
                ->get();
            
            $relatedCourses = $relatedCourses->merge($additionalCourses);
        }
        
        // If still not enough, add some random active courses
        if ($relatedCourses->count() < 3) {
            $randomCourses = Course::where('id', '!=', $this->id)
                ->where('status', self::STATUS_ACTIVE)
                ->whereNotIn('id', $relatedCourses->pluck('id'))
                ->with(['reviews.user', 'instructors'])
                ->limit(5 - $relatedCourses->count())
                ->get();
            
            $relatedCourses = $relatedCourses->merge($randomCourses);
        }
        
        return $relatedCourses;
    }

    /**
     * Get remedies for this course
     */
    public function getRemediesAttribute()
    {
        $remedyIds = $this->selectedRemedies ?? [];
        return Remedy::with(['reviews.user'])->whereIn('id', $remedyIds)->get();
    }
}
