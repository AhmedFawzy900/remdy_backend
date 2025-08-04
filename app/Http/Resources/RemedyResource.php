<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemedyResource extends JsonResource
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
            'main_image_url' => $this->main_image_url,
            'disease' => $this->whenLoaded('diseaseRelation', function() {
                return $this->diseaseRelation->name;
            }, $this->getAttribute('disease')),
            'remedy_type' => new RemedyTypeResource($this->whenLoaded('remedyType')),
            'body_system' => new BodySystemResource($this->whenLoaded('bodySystem')),
            'description' => $this->description,
            'visible_to_plan' => $this->visible_to_plan,
            'status' => $this->status,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'benefits' => $this->benefits,
            'precautions' => $this->precautions,
            'product_link' => $this->product_link,
            'reviews' => $reviews instanceof \Illuminate\Http\Resources\MissingValue ? [] : ReviewResource::collection($reviews->take(2)),
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
