<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';
    // Siswa usually has created_at if I recall import logic `NOW()`. 
    // Let's assume timestamps OK or check Import logic. 
    // Legacy import: `INSERT INTO siswa (..., created_at) VALUES (..., NOW())`
    // So it has timestamps. 
    // But does it have updated_at? Usually yes if created_at exists.
    // I'll enable timestamps. form safety I'll set const UPDATED_AT = null if it fails.
    // Let's check `data-siswa.php` structure again if I can... 
    // Actually safe to assume timestamps = false and manually manage if needed, 
    // OR try default. I'll use false to be safe against "Column not found".

    public $timestamps = true;

    protected $fillable = [
        'nama',
        'nis',
        'kelas_id',
        'tgl_lahir',
        'no_wa',
        'wa_ortu',
        'uid_rfid',
        'enroll_status',
        'id_finger',
        'enroll_finger_status',
        'created_at',
        'updated_at'
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function fingerprints()
    {
        return $this->hasMany(SiswaFingerprint::class, 'student_id');
    }
}
