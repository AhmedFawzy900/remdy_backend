<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image',
        'full_name',
        'subscription_plan',
        'account_status',
        'phone',
        'account_verification',
        'otp',
        'otp_source',
        'otp_expired_date',
        'code_usage',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_source',
        'otp_expired_date',
        'code_usage',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expired_date' => 'date',
        ];
    }

    // Subscription Plan Constants
    const PLAN_BASIC = 'basic';
    const PLAN_PREMIUM = 'premium';
    const PLAN_PRO = 'pro';
    const PLAN_ROOKIE = 'rookie';
    const PLAN_MASTER = 'master';
    const PLAN_SKILLED = 'skilled';

    // Account Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_BLOCKED = 'blocked';

    public function scopeActive($query)
    {
        return $query->where('account_status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('account_status', self::STATUS_INACTIVE);
    }

    public function scopeSuspended($query)
    {
        return $query->where('account_status', self::STATUS_SUSPENDED);
    }

    public function scopeBasic($query)
    {
        return $query->where('subscription_plan', self::PLAN_BASIC);
    }

    public function scopePremium($query)
    {
        return $query->where('subscription_plan', self::PLAN_PREMIUM);
    }

    public function scopePro($query)
    {
        return $query->where('subscription_plan', self::PLAN_PRO);
    }

    /**
     * Get the user's favorites.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the user's favorite remedies.
     */
    public function favoriteRemedies()
    {
        return $this->belongsToMany(Remedy::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favoritable_type', Remedy::class);
    }

    /**
     * Get the user's favorite articles.
     */
    public function favoriteArticles()
    {
        return $this->belongsToMany(Article::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favoritable_type', Article::class);
    }

    /**
     * Get the user's favorite courses.
     */
    public function favoriteCourses()
    {
        return $this->belongsToMany(Course::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favoritable_type', Course::class);
    }

    /**
     * Get the user's favorite videos.
     */
    public function favoriteVideos()
    {
        return $this->belongsToMany(Video::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favoritable_type', Video::class);
    }

    /**
     * Get the user's reminders.
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }
}
