<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanHistory extends Model
{
    protected $table = 'scan_history';
    public $timestamps = false;
    protected $fillable = ['uid', 'created_at'];
}
