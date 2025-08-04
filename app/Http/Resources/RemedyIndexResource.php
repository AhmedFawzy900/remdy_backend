<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemedyIndexResource extends JsonResource
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
            'remedy_type' => $this->whenLoaded('remedyType', function() {
                return $this->remedyType ? $this->remedyType->name : null;
            }),
            'body_system' => $this->whenLoaded('bodySystem', function() {
                return $this->bodySystem ? $this->bodySystem->title : null;
            }),
            'description' => $this->description,
            'visible_to_plan' => $this->visible_to_plan,
            'status' => $this->status,
            'product_link' => $this->product_link,
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 