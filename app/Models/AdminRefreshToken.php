<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRefreshToken extends Model
{
    protected $table = 'admin_refresh_tokens';
    protected $fillable = [
        'admin_id',
        'token',
        'expires_at',
    ];

    protected $dates = [
        'expires_at',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
} 