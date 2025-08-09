<?php

namespace App\Http\Resources\Mobile;

use App\Http\Resources\InstructorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyCourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'course' => [
                'id' => $this['course']->course_id,
                'title' => $this['course']->title,
                'description' => $this['course']->description,
                'image' => $this['course']->image,
                'price' => $this['course']->price,
                'duration' => $this['course']->duration,
                'sessionsNumber' => $this['course']->sessionsNumber ?? null,
                'plan' => $this['course']->plan ?? null,
                'overview' => $this['course']->overview ?? null,
                'courseContent' => $this['course']->courseContent ?? null,
                'status' => $this['course']->status,
                'instructor' => $this['course']->instructors ? InstructorResource::collection($this['course']->instructors) : null,
                'lessons' => $this['course']->lessons->map(function($lesson) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'image' => $lesson->image,
                        'status' => $lesson->status,
                    ];
                }),
                'created_at' => $this['course']->created_at,
                'updated_at' => $this['course']->updated_at,
            ],
            'progress' => [
                'percentage' => $this['progress'],
                'total_lessons' => $this['total_lessons'],
                'completed_lessons' => $this['completed_lessons'],
                'remaining_lessons' => $this['total_lessons'] - $this['completed_lessons'],
                'is_started' => $this['started'],
            ],
        ];
    }
} 