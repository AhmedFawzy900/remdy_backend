<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewReaction extends Model
{
    protected $fillable = [
        'user_id',
        'review_id',
        'reaction_type',
    ];

    protected $casts = [
        'reaction_type' => 'string',
    ];

    const REACTION_LIKE = 'like';
    const REACTION_DISLIKE = 'dislike';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function scopeLike($query)
    {
        return $query->where('reaction_type', self::REACTION_LIKE);
    }

    public function scopeDislike($query)
    {
        return $query->where('reaction_type', self::REACTION_DISLIKE);
    }
}
