<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
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
            'videoLink' => $this->videoLink,
            'title' => $this->title,
            'description' => $this->description,
            'visiblePlans' => $this->visiblePlans,
            'status' => $this->status,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'benefits' => $this->benefits,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
