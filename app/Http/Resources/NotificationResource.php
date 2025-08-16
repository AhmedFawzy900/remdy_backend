<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "image" => $this->image,
            "action_url" => $this->action_url,
            "seen" => $this->seen ? true : false,
            'ago' => [
                    'date' => $this->created_at->format('l d F Y'), // Exact date in English
                    'diff' => $this->created_at->diffForHumans(), // Relative difference in English
                ],
        ];
    }
} 