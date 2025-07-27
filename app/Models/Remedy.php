<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remedy extends Model
{
    protected $fillable = [
        'title',
        'main_image_url',
        'disease',
        'remedy_type_id',
        'body_system_id',
        'description',
        'visible_to_plan',
        'status',
        'ingredients',
        'instructions',
        'benefits',
        'precautions',
    ];

    protected $casts = [
        'visible_to_plan' => 'string',
        'ingredients' => 'array',
        'instructions' => 'array',
        'benefits' => 'array',
        'precautions' => 'array',
        'status' => 'string',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const PLAN_ALL = 'all';
    const PLAN_SKILLED = 'skilled';
    const PLAN_MASTER = 'master';
    const PLAN_ROOKIE = 'rookie';

    public function remedyType()
    {
        return $this->belongsTo(RemedyType::class);
    }

    public function bodySystem()
    {
        return $this->belongsTo(BodySystem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}
