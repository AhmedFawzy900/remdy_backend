<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutNotification extends Model
{
    use HasFactory;
    protected $table = 'out_notifications';
    protected $fillable = [
        'type',
        'title',
        'description',
        'image',
        'user_ids',
        'guest_ids',
        'action_url',
        'seen',
    ];



}
