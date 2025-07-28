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
        // Calculate average rating and review count
        $reviews = $this->whenLoaded('reviews');
        $averageRating = 0;
        $reviewCount = 0;
        
        if ($reviews && $reviews->count() > 0) {
            $averageRating = round($reviews->avg('rate'), 1);
            $reviewCount = $reviews->count();
        }

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
            'remedies' => $this->remedies ? RemedyResource::collection($this->remedies) : [],
            'relatedCourses' => $this->relatedCourses,
            'status' => $this->status,
            'sessions' => $this->sessions,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
