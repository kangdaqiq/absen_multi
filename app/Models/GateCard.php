<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateCard extends Model
{
    use HasFactory;

    protected $table = 'gate_cards';

    protected $fillable = [
        'school_id',
        'guru_id',
        'uid_rfid',
        'name',
        'enroll_status',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }
}
