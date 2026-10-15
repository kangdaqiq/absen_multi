<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';
    public $timestamps = false;
    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'wali_kelas_id',
        'wali_kelas_2_id',
        'wa_group_id',
        'is_active_attendance',
        'is_active_report',
        'school_id',
        'jurusan_id'
    ];

    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_id');
    }

    public function waliKelas2()
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_2_id');
    }

    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class, 'kelas_id');
    }

    public function mapels()
    {
        return $this->belongsToMany(Mapel::class, 'kelas_mapel', 'kelas_id', 'mapel_id');
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    public function getNamaAttribute()
    {
        return $this->nama_kelas;
    }

    public function getTingkatLabelAttribute(): string
    {
        return $this->tingkat ? "Kelas {$this->tingkat}" : "-";
    }
}
