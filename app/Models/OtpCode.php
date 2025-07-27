<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'code',
        'type',
        'expires_at',
        'verified_at',
    ];

    protected $dates = [
        'expires_at',
        'verified_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
} 