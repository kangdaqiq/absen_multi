<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'price_monthly',
        'price_yearly',
        'student_limit',
        'teacher_limit',
        'bot_user_limit',
        'history_quota_months',
        'wa_enabled',
        'bot_enabled',
        'is_active',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'wa_enabled' => 'boolean',
        'bot_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
