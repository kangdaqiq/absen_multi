<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiGuru extends Model
{
    use HasFactory;
    
    protected $table = 'absensi_guru';
    
    protected $fillable = [
        'guru_id',
        'jadwal_pelajaran_id',
        'tanggal',
        'waktu_hadir',
        'status'
    ];
    
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
    
    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }
}
