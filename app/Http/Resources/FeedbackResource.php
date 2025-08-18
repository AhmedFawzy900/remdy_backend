<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
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
			'user_id' => $this->user_id,
			'rating' => $this->rating,
			'message' => $this->message,
			'device' => $this->device,
			'app_version' => $this->app_version,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
		];
	}
}


