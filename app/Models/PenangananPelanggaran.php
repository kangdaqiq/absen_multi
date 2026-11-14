<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenangananPelanggaran extends Model
{
    use HasFactory;

    protected $table = 'penanganan_pelanggarans';

    protected $fillable = [
        'school_id',
        'pelanggaran_id',
        'student_id',
        'handled_by',
        'tanggal',
        'tindakan_sp',
        'catatan_penanganan',
        'hasil_kesepakatan',
        'link_gdrive',
        'lampiran_file',
        'status_penanganan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function pelanggaran()
    {
        return $this->belongsTo(Pelanggaran::class, 'pelanggaran_id');
    }

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function getTindakanSpLabelAttribute(): string
    {
        return match ($this->tindakan_sp) {
            'sp_1'              => 'Surat Peringatan 1 (SP 1)',
            'sp_2'              => 'Surat Peringatan 2 (SP 2)',
            'sp_3'              => 'Surat Peringatan 3 (SP 3)',
            'sp_4'              => 'Surat Peringatan 4 (SP 4 / Terakhir)',
            'pembinaan'         => 'Pembinaan BK',
            'teguran_lisan'     => 'Teguran Lisan',
            'teguran_tertulis'  => 'Teguran Tertulis',
            'skorsing'          => 'Skorsing',
            'dikembalikan_ortu' => 'Dikembalikan ke Orang Tua',
            default             => strtoupper(str_replace('_', ' ', (string) $this->tindakan_sp)),
        };
    }

    public function getStatusPenangananLabelAttribute(): string
    {
        return match ($this->status_penanganan) {
            'selesai'           => 'Selesai',
            'dalam_proses'      => 'Dalam Proses',
            'evaluasi_lanjutan' => 'Evaluasi Lanjutan',
            default             => ucfirst(str_replace('_', ' ', (string) $this->status_penanganan)),
        };
    }

    public function getLampiranFileUrlAttribute(): ?string
    {
        if (!$this->lampiran_file) {
            return null;
        }

        return str_starts_with($this->lampiran_file, 'http')
            ? $this->lampiran_file
            : asset('storage/' . ltrim($this->lampiran_file, '/'));
    }
}

