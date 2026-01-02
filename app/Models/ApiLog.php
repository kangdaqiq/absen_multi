<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $table = 'api_logs';
    public $timestamps = false; // Manually managed in legacy logic
    protected $fillable = ['api_key', 'action', 'uid', 'success', 'message', 'ip_address', 'user_agent', 'created_at'];
}
