<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pelanggaran extends Model
{
    use HasFactory;

    protected $table = 'pelanggarans';

    protected $fillable = [
        'school_id',
        'student_id',
        'kategori_pelanggaran_id',
        'recorded_by',
        'code',
        'tanggal',
        'waktu',
        'judul_pelanggaran',
        'kategori',
        'poin',
        'keterangan',
        'tindak_lanjut',
        'bukti_foto',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'poin'    => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function kategoriPelanggaran()
    {
        return $this->belongsTo(KategoriPelanggaran::class, 'kategori_pelanggaran_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'ringan' => 'Ringan',
            'sedang' => 'Sedang',
            'berat'  => 'Berat',
            default  => ucfirst((string) $this->kategori),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'tercatat' => 'Tercatat',
            'diproses' => 'Diproses',
            'selesai'  => 'Selesai',
            default    => ucfirst((string) $this->status),
        };
    }

    public function getBuktiFotoUrlAttribute(): ?string
    {
        if (!$this->bukti_foto) {
            return null;
        }

        return str_starts_with($this->bukti_foto, 'http')
            ? $this->bukti_foto
            : asset('storage/' . ltrim($this->bukti_foto, '/'));
    }

    public static function generateCode(): string
    {
        do {
            $code = 'PLG-' . strtoupper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}

