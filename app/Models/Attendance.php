<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';
    protected $fillable = [
        'student_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'total_seconds',
        'status',
        'keterangan',
        'is_auto_extended',
        'is_auto_alpha',
        'photo_checkin_path',
        'face_similarity_score',
        'is_face_matched',
        'created_at',
        'updated_at'
    ];

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }
}
