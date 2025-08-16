<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class RemedyDashboardResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];

        // Add full relationships data for dashboard (singular for backward-compatibility)
        if ($this->relationLoaded('remedyType') && $this->remedyType) {
            $data['remedy_type'] = new RemedyTypeResource($this->remedyType);
        }

        if ($this->relationLoaded('bodySystem') && $this->bodySystem) {
            $data['body_system'] = new BodySystemResource($this->bodySystem);
        }

        if ($this->relationLoaded('diseaseRelation') && $this->diseaseRelation) {
            $data['disease_relation'] = new DiseaseResource($this->diseaseRelation);
        }

        // New: plural arrays (ids and full objects) when loaded
        if ($this->relationLoaded('remedyTypes')) {
            $data['remedy_type_ids'] = $this->remedyTypes->pluck('id');
            $data['remedy_types'] = RemedyTypeResource::collection($this->remedyTypes);
        }
        if ($this->relationLoaded('bodySystems')) {
            $data['body_system_ids'] = $this->bodySystems->pluck('id');
            $data['body_systems'] = BodySystemResource::collection($this->bodySystems);
        }
        if ($this->relationLoaded('diseases')) {
            $data['disease_ids'] = $this->diseases->pluck('id');
            $data['diseases'] = DiseaseResource::collection($this->diseases);
        }

        // Add is_fav field - always present
        

            $data['is_fav'] = false;
        

        return $data;
    }
} 