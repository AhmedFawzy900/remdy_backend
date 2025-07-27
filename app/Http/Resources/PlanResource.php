<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly,
            'trial_days' => $this->trial_days,
            'description' => $this->description,
            'features' => $this->features,
            'badge_emoji' => $this->badge_emoji,
            'status' => $this->status,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 