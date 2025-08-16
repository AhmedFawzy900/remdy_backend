<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\ReviewReaction;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get current user's reaction to this review
        $isLiked = null;
        
        // Try to get the authenticated user from multiple guards
        $user = null;
        
        // Try sanctum guard first (for API tokens)
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
        }
        // Fallback to web guard (for session auth)
        elseif (Auth::check()) {
            $user = Auth::user();
        }
        // Fallback to bearer token from request
        elseif ($request->bearerToken()) {
            try {
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken())->tokenable;
            } catch (\Exception $e) {
                $user = null;
            }
        }
        
        if ($user) {
            // Check if reactions are loaded, if not, query the database
            if ($this->relationLoaded('reactions')) {
                // Use the loaded reactions relationship
                $userReaction = $this->reactions->where('user_id', $user->id)->first();
            } else {
                // Query the database directly
                $userReaction = ReviewReaction::where('review_id', $this->id)->where('user_id', $user->id)->first();
            }
            
            if ($userReaction) {
                $isLiked = $userReaction->reaction_type === ReviewReaction::REACTION_LIKE;
            }
        }
        // If no user is authenticated, isLiked remains null (which is correct behavior)

        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'type' => $this->type,
            'element_id' => $this->element_id,
            'rate' => $this->rate,
            'message' => $this->message,
            'status' => $this->status,
            'likes_count' => $this->likes_count ?? $this->likes()->count(),
            'dislikes_count' => $this->dislikes_count ?? $this->dislikes()->count(),
            'isLiked' => $isLiked, // true = liked, false = disliked, null = no reaction
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 