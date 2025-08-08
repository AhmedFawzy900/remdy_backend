<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonIndexResource extends JsonResource
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
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'whats_included' => $this->whats_included ?? [],
            'activities' => $this->activities ?? [],
            'video' => $this->video ?? [],
            'instructions' => $this->instructions ?? [],
            'ingredients' => $this->ingredients ?? [],
            'tips' => $this->tips ?? [],
            'status' => $this->status,
            'order' => $this->order,
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'title' => $this->course->title,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
