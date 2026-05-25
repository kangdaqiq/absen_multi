<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KegiatanAttendance extends Model
{
    protected $table = 'kegiatan_attendances';
    public $timestamps = true;

    protected $fillable = [
        'school_id',
        'kegiatan_id',
        'student_id',
        'tanggal',
        'jam_masuk',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kegiatan_id');
    }

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
