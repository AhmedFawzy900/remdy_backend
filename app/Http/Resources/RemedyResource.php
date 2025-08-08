<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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
        $data = [
            'id' => $this->id,
            'title' => $this->title,
           
            'description' => $this->description,
            'main_image_url' => $this->main_image_url,
            'status' => $this->status,
            'visible_to_plan' => $this->visible_to_plan,
            'product_link' => $this->product_link,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'benefits' => $this->benefits,
            'precautions' => $this->precautions,
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Add relationships if loaded - just string titles
        if ($this->relationLoaded('remedyType') && $this->remedyType) {
            $data['remedy_type'] = $this->remedyType->name;
        }

        if ($this->relationLoaded('bodySystem') && $this->bodySystem) {
            $data['body_system'] = $this->bodySystem->title;
        }

        if ($this->relationLoaded('diseaseRelation') && $this->diseaseRelation) {
            $data['disease_relation'] = $this->diseaseRelation->name;
        }

        if ($this->relationLoaded('reviews')) {
            $data['reviews'] = ReviewResource::collection($this->reviews);
        }

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
                ->where('favoritable_type', 'remedy')
                ->where('favoritable_id', $this->id)
                ->exists();
        } else {
            $data['is_fav'] = false;
        }

        return $data;
    }
}
