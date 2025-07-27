<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image' => $this->image,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'sessionsNumber' => $this->sessionsNumber,
            'price' => $this->price,
            'plan' => $this->plan,
            'overview' => $this->overview,
            'courseContent' => $this->courseContent,
            'instructors' => $this->instructors,
            'selectedRemedies' => $this->selectedRemedies,
            'remedies' => RemedyResource::collection($this->remedies ?? collect()),
            'relatedCourses' => $this->relatedCourses,
            'status' => $this->status,
            'sessions' => $this->sessions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
