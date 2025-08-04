<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
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
        
        if ($reviews && !$reviews instanceof \Illuminate\Http\Resources\MissingValue && $reviews->count() > 0) {
            $averageRating = round($reviews->avg('rate'), 1);
            $reviewCount = $reviews->count();
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image,
            'description' => $this->description,
            'plants' => $this->plants,
            'plans' => $this->plans,
            'status' => $this->status,
            'reviews' => $reviews instanceof \Illuminate\Http\Resources\MissingValue ? [] : ReviewResource::collection($reviews->take(2)),
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
