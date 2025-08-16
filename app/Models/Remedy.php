<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remedy extends Model
{
    protected $fillable = [
        'title',
        'main_image_url',
        'disease',
        'disease_id',
        'remedy_type_id',
        'body_system_id',
        'description',
        'visible_to_plan',
        'status',
        'ingredients',
        'instructions',
        'benefits',
        'precautions',
        'product_link',
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

    public function diseaseRelation()
    {
        return $this->belongsTo(Disease::class, 'disease_id');
    }

    // New: many-to-many relations (backward-compatible in parallel with singular)
    public function remedyTypes()
    {
        return $this->belongsToMany(RemedyType::class, 'remedy_remedy_type');
    }

    public function bodySystems()
    {
        return $this->belongsToMany(BodySystem::class, 'body_system_remedy');
    }

    public function diseases()
    {
        return $this->belongsToMany(Disease::class, 'disease_remedy');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'element_id')->where('type', 'remedy')->where('status', 'accepted');
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
