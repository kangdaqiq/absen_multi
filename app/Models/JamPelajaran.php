<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JamPelajaran extends Model
{
    protected $table = 'jam_pelajaran';

    protected $fillable = [
        'school_id',
        'nama_jam',
        'jam_ke',
        'jam_mulai',
        'jam_selesai',
        'hari',
        'urutan',
        'is_active',
        'keterangan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function jadwalPelajarans()
    {
        return $this->hasMany(JadwalPelajaran::class, 'jam_pelajaran_id');
    }

    /**
     * Format jam mulai (H:i)
     */
    public function getFormattedJamMulaiAttribute(): string
    {
        return $this->jam_mulai ? Carbon::parse($this->jam_mulai)->format('H:i') : '-';
    }

    /**
     * Format jam selesai (H:i)
     */
    public function getFormattedJamSelesaiAttribute(): string
    {
        return $this->jam_selesai ? Carbon::parse($this->jam_selesai)->format('H:i') : '-';
    }

    /**
     * Format rentang jam: 07:15 - 08:45
     */
    public function getRentangWaktuAttribute(): string
    {
        return "{$this->formatted_jam_mulai} - {$this->formatted_jam_selesai}";
    }

    /**
     * Menghitung durasi menit jam pelajaran
     */
    public function getDurasiMenitAttribute(): int
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return 0;
        }

        $start = Carbon::parse($this->jam_mulai);
        $end = Carbon::parse($this->jam_selesai);

        return abs($start->diffInMinutes($end));
    }

    /**
     * Label lengkap untuk dropdown: "Jam Ke 1 (07:15 - 08:00)"
     */
    public function getLabelAttribute(): string
    {
        $sesi = $this->jam_ke ? " [Jam ke {$this->jam_ke}]" : '';
        return "{$this->nama_jam}{$sesi} ({$this->rentang_waktu})";
    }

    /**
     * Dapatkan daftar hari berlaku sebagai array
     */
    public function getHariArrayAttribute(): array
    {
        if (!$this->hari) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $this->hari))));
    }
}
