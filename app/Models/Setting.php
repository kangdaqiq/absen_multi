<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = false;
    protected $fillable = ['setting_key', 'setting_value'];
    protected $primaryKey = 'setting_key'; // Assuming key is primary or unique
    public $incrementing = false;
    protected $keyType = 'string';
}
