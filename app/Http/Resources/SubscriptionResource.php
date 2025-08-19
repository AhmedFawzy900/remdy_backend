<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'plan' => $this->subscription_plan,
            'interval' => $this->subscription_interval,
            'started_at' => $this->subscription_started_at,
            'ends_at' => $this->subscription_ends_at,
            'trial_ends_at' => $this->trial_ends_at,
            'is_active' => (bool) ($this->subscription_ends_at && now()->lt($this->subscription_ends_at))
                || (bool) ($this->trial_ends_at && now()->lt($this->trial_ends_at)),
            'last_subscription_reference' => $this->last_subscription_reference,
            'account_status' => $this->account_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


