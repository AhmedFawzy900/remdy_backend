<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailResource extends JsonResource
{
    private $purchase;
    private $started;

    public function __construct($resource, $purchase = null, $started = false)
    {
        parent::__construct($resource);
        $this->purchase = $purchase;
        $this->started = $started;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'image' => $this->image,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'sessionsNumber' => $this->sessionsNumber ?? null,
            'price' => $this->price,
            'plan' => $this->plan ?? null,
            'overview' => $this->overview ?? null,
            'courseContent' => $this->courseContent ?? null,
            'instructors' => $this->whenLoaded('instructors', function () {
                return $this->instructors;
            }),
            'remedies' => $this->remedies ?? [],
            'relatedCourses' => $this->relatedCourses ?? [],
            'status' => $this->status,
            'reviews' => $this->reviews ?? [],
            'average_rating' => $this->rating ?? 0,
            'review_count' => $this->reviews ? $this->reviews->count() : 0,
            'lessons_count' => $this->lessons->count(),
            'purchase_status' => !is_null($this->purchase),
            'is_started' => $this->started,
            'can_access' => !is_null($this->purchase),
           
        ];

        // Add is_fav field - always present
        $user = null;
        if ($request->bearerToken()) {
            try {
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken())->tokenable;
            } catch (\Exception $e) {
                $user = null;
            }
        }
        
        if ($user) {
            $data['is_fav'] = \App\Models\Favorite::where('user_id', $user->id)
                ->where('favoritable_type', 'course')
                ->where('favoritable_id', $this->id)
                ->exists();
        } else {
            $data['is_fav'] = false;
        }

        // Coming lesson id based on progress (first not-completed lesson, or first lesson if none started)
        $comingLessonId = null;
        $lessons = $this->lessons ?? collect();
        if ($lessons && $lessons->count() > 0) {
            if ($user) {
                $progressByLessonId = \App\Models\LessonProgress::where('user_id', $user->id)
                    ->where('course_id', $this->id)
                    ->get()
                    ->keyBy('lesson_id');

                $nextLesson = $lessons->first(function ($lesson) use ($progressByLessonId) {
                    $progress = $progressByLessonId->get($lesson->id);
                    return !$progress || $progress->status !== 'completed';
                });

                $comingLessonId = $nextLesson ? $nextLesson->id : null;
            } else {
                $comingLessonId = $lessons->first()->id;
            }
        }
        $data['coming_lesson_id'] = $comingLessonId;

        return $data;
    }
} 