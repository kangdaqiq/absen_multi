<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalPelajaran extends Model
{
    use HasFactory;

    protected $table = 'jurnal_pelajaran';

    protected $fillable = [
        'school_id',
        'jadwal_pelajaran_id',
        'guru_id',
        'tanggal',
        'jam_ke',
        'materi',
        'catatan',
        'jam_mulai',
        'jam_selesai',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function absensis()
    {
        return $this->hasMany(AbsensiPelajaran::class, 'jurnal_pelajaran_id');
    }
}
