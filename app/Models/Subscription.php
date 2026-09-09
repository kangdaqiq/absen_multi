<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'invoice_token',
        'invoice_number',
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

    protected static function booted()
    {
        static::creating(function ($subscription) {
            if (empty($subscription->invoice_token)) {
                $subscription->invoice_token = bin2hex(random_bytes(16));
            }
        });

        static::created(function ($subscription) {
            if (empty($subscription->invoice_number)) {
                $subscription->updateQuietly([
                    'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad($subscription->id, 4, '0', STR_PAD_LEFT)
                ]);
            }
        });
    }

    public function ensureInvoiceDetails(): self
    {
        $dirty = false;
        if (empty($this->invoice_token)) {
            $this->invoice_token = bin2hex(random_bytes(16));
            $dirty = true;
        }
        if (empty($this->invoice_number)) {
            $this->invoice_number = 'INV-' . ($this->created_at ? $this->created_at->format('Ymd') : date('Ymd')) . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
            $dirty = true;
        }
        if ($dirty) {
            $this->saveQuietly();
        }
        return $this;
    }

    public function getPublicInvoiceUrlAttribute(): string
    {
        $this->ensureInvoiceDetails();
        return url("/invoice/{$this->invoice_token}");
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
