<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruShiftAssignment extends Model
{
    use HasFactory;

    protected $table = 'guru_shift_assignments';

    protected $fillable = [
        'school_id',
        'guru_id',
        'shift_id',
        'tanggal',
        'index_hari',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'index_hari' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
