<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportGroup extends Model
{
    protected $fillable = [
        'name',
        'jid',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
