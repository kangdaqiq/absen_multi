<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';
    protected $fillable = ['student_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'total_seconds', 'status', 'keterangan', 'created_at', 'updated_at'];

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }
}
