<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'type',
        'version',
        'content',
        'is_active',
        'is_popup',
        'action_url',
        'action_text',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_popup' => 'boolean',
    ];

    protected $appends = [
        'type_label',
        'type_icon',
        'type_badge_class',
        'header_gradient_class',
    ];


    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'release' => 'Update Rilis',
            'feature' => 'Fitur Baru',
            'warning' => 'Penting',
            'maintenance' => 'Pemeliharaan',
            default => 'Informasi',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'release' => 'fa-rocket',
            'feature' => 'fa-sparkles',
            'warning' => 'fa-triangle-exclamation',
            'maintenance' => 'fa-screwdriver-wrench',
            default => 'fa-bullhorn',
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'release' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            'feature' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'maintenance' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            default => 'bg-brand-50 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300 border-brand-200 dark:border-brand-800',
        };
    }

    public function getHeaderGradientClassAttribute(): string
    {
        return match ($this->type) {
            'release' => 'from-purple-600 via-indigo-600 to-blue-600',
            'feature' => 'from-emerald-600 to-teal-600',
            'warning' => 'from-amber-500 to-orange-600',
            'maintenance' => 'from-blue-600 to-cyan-600',
            default => 'from-brand-600 to-indigo-600',
        };
    }
}

