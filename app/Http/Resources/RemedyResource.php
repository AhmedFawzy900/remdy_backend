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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'main_image_url' => $this->main_image_url,
            'disease' => $this->disease,
            'remedy_type' => new RemedyTypeResource($this->whenLoaded('remedyType')),
            'body_system' => new BodySystemResource($this->whenLoaded('bodySystem')),
            'description' => $this->description,
            'visible_to_plan' => $this->visible_to_plan,
            'status' => $this->status,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'benefits' => $this->benefits,
            'precautions' => $this->precautions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
