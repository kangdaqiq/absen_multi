<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramLog extends Model
{
    protected $fillable = [
        'school_id',
        'chat_id',
        'message',
        'status',
        'error',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
