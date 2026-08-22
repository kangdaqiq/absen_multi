<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'school_id',
        'package_id',
        'amount',
        'unique_code',
        'status',
        'billing_cycle',
        'started_at',
        'expired_at',
        'paid_at',
        'payment_method',
        'payment_ref',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'unique_code' => 'integer',
        'started_at' => 'datetime',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
