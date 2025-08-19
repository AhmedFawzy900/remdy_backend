<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $table = 'guests';

    protected $fillable = [
        'token',
        'device',
    ];
    public function scopeFilterForNotification($query, array $conditions)
    {
        return $query->get()->map(function ($guest) {
            return [
                'fcm_tokens' =>[
                    [
                        'token' => $guest->token,
                        ]
                    ],
                    'id' => $guest->id
            ];
        });
    }

    public function outNotifications()
    {
        return $this->hasMany(OutNotification::class);
    }
}
