<?php

namespace App\Http\Resources\Mobile;

use App\Http\Resources\CourseIndexResource;
use App\Http\Resources\InstructorResource;
use App\Http\Resources\RemedyIndexResource;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Calculate average rating and review count
        $reviews = $this->whenLoaded('reviews');
        $averageRating = 0;
        $reviewCount = 0;
        
        if ($reviews && !$reviews instanceof \Illuminate\Http\Resources\MissingValue && $reviews->count() > 0) {
            $averageRating = round($reviews->avg('rate'), 1);
            $reviewCount = $reviews->count();
        }

        $data = [
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
            'instructors' => $this->whenLoaded('instructors', function () {
                return InstructorResource::collection($this->instructors);
            }),
            'remedies' => $this->remedies ? RemedyIndexResource::collection($this->remedies) : [],
            'relatedCourses' => CourseIndexResource::collection($this->relatedCourses),
            'status' => $this->status,
            'reviews' => $reviews instanceof \Illuminate\Http\Resources\MissingValue ? [] : ReviewResource::collection($reviews->take(2)),
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'purchase_status' => $this->purchase_status,
            'course_status' => $this->course_status,
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

        return $data;
    }
} 
