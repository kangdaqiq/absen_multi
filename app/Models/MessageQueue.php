<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageQueue extends Model
{
    protected $table = 'message_queues';
    public $timestamps = true;
    protected $fillable = ['school_id', 'phone_number', 'message', 'status', 'attempts', 'last_error', 'created_at', 'updated_at'];
}
