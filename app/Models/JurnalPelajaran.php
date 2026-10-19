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
        'dokumentasi',
        'jam_mulai',
        'jam_selesai',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'dokumentasi' => 'array',
    ];

    protected $appends = [
        'dokumentasi_list',
        'has_dokumentasi',
    ];

    /**
     * Aksesor: Daftar berkas dokumentasi KBM (foto/video) terformat rapi
     */
    public function getDokumentasiListAttribute(): array
    {
        $raw = $this->dokumentasi;
        if (empty($raw)) {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $list = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $ext = strtolower(pathinfo(parse_url($item, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', '3gp']);
                $list[] = [
                    'url'        => $item,
                    'view_url'   => $item,
                    'download_url' => $item,
                    'type'       => $isVideo ? 'video' : 'image',
                    'name'       => basename(parse_url($item, PHP_URL_PATH) ?? 'file'),
                    'is_video'   => $isVideo,
                    'is_image'   => !$isVideo,
                ];
            } elseif (is_array($item)) {
                $url = $item['view_url'] ?? ($item['url'] ?? ($item['download_url'] ?? ''));
                $mime = strtolower($item['mime_type'] ?? '');
                $ext = strtolower($item['extension'] ?? pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', '3gp']) || (($item['type'] ?? '') === 'video');

                $list[] = [
                    'id'           => $item['id'] ?? null,
                    'url'          => $url,
                    'view_url'     => $url,
                    'download_url' => $item['download_url'] ?? $url,
                    'type'         => $isVideo ? 'video' : 'image',
                    'name'         => $item['original_name'] ?? ($item['name'] ?? basename(parse_url($url, PHP_URL_PATH) ?? 'file')),
                    'size'         => $item['size'] ?? null,
                    'human_size'   => $item['human_size'] ?? null,
                    'mime_type'    => $item['mime_type'] ?? null,
                    'is_video'     => $isVideo,
                    'is_image'     => !$isVideo,
                ];
            }
        }

        return $list;
    }

    /**
     * Aksesor: Apakah memiliki dokumentasi KBM
     */
    public function getHasDokumentasiAttribute(): bool
    {
        return !empty($this->dokumentasi_list);
    }

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
