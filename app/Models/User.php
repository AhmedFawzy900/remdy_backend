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
        'subscription_interval',
        'subscription_started_at',
        'subscription_ends_at',
        'trial_ends_at',
        'has_used_trial',
        'last_subscription_reference',
        'account_status',
        'phone',
        'account_verification',
        'otp',
        'otp_source',
        'otp_expired_date',
        'code_usage',
        'google_id',
        'apple_id',
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
            'subscription_started_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'has_used_trial' => 'boolean',
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
            ->where('favoritable_type', 'remedy');
    }

    /**
     * Get the user's favorite articles.
     */
    public function favoriteArticles()
    {
        return $this->belongsToMany(Article::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favoritable_type', 'article');
    }

    /**
     * Get the user's favorite courses.
     */
    public function favoriteCourses()
    {
        return $this->belongsToMany(Course::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favoritable_type', 'course');
    }

    /**
     * Get the user's favorite videos.
     */
    public function favoriteVideos()
    {
        return $this->belongsToMany(Video::class, 'favorites', 'user_id', 'favoritable_id')
            ->where('favoritable_type', 'video');
    }

    /**
     * Get the user's reminders.
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get course purchases for this user.
     */
    public function coursePurchases()
    {
        return $this->hasMany(CoursePurchase::class);
    }

    /**
     * Get lesson progress for this user.
     */
    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->subscription_ends_at && now()->lt($this->subscription_ends_at)) {
            return true;
        }
        if ($this->trial_ends_at && now()->lt($this->trial_ends_at)) {
            return true;
        }
        return false;
    }

    public function isAtLeastSkilled(): bool
    {
        return in_array($this->subscription_plan, [self::PLAN_SKILLED, self::PLAN_MASTER], true);
    }

    public function scopeFilterForNotification($query, array $conditions)
    {
        // Filter by IDs if provided (companies or individual users)
        // if (!empty($conditions['users'])) {
        //     $query->whereIn('id', $conditions['users']);
        // }
        $query->where('account_verification', 'yes');
        // Return only id and fcm_token
        return $query->with('deviceTokens:token,user_id')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'fcm_tokens' => $user->deviceTokens->toArray(),
            ];
        });
    }

    public function outNotifications()
    {
        return $this->hasMany(OutNotification::class);
    }
}
