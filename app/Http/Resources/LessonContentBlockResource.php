<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonContentBlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'video_url' => $this->video_url,
            'content' => $this->content,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // If this is a remedy block and remedy is loaded, include remedy data
        if ($this->type === 'remedy' && $this->relationLoaded('remedy') && $this->remedy) {
            $data['remedy'] = [
                'id' => $this->remedy->id,
                'title' => $this->remedy->title,
                'description' => $this->remedy->description,
                'main_image_url' => $this->remedy->main_image_url,
                'ingredients' => $this->remedy->ingredients,
                'instructions' => $this->remedy->instructions,
                'benefits' => $this->remedy->benefits,
                'precautions' => $this->remedy->precautions,
                'product_link' => $this->remedy->product_link,
                'status' => $this->remedy->status,
            ];
        }

        return $data;
    }
} 