<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPelanggaran extends Model
{
    use HasFactory;

    protected $table = 'kategori_pelanggarans';

    protected $fillable = [
        'school_id',
        'kode',
        'nama',
        'kategori',
        'poin',
        'sanksi_default',
        'is_active',
    ];

    protected $casts = [
        'poin'      => 'integer',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function pelanggarans()
    {
        return $this->hasMany(Pelanggaran::class, 'kategori_pelanggaran_id');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
}

