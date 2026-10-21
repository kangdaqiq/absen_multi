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

        $school = $this->relationLoaded('school') ? $this->school : ($this->school_id ? \App\Models\School::find($this->school_id) : null);
        $storageKey = $school?->storage_api_key ?: config('services.storage_server.key') ?: '812194c6c53ee6557e088b897f5ec08abafb49a8fa5e656f5acf75f70a93145f';

        // Helper untuk menyertakan api_key pada URL storage server agar browser tidak terkena 401 Unauthorized
        $attachApiKey = function (?string $url) use ($storageKey): ?string {
            if (empty($url) || empty($storageKey)) {
                return $url;
            }
            if (str_contains($url, '/api/v1/files/') && !str_contains($url, 'api_key=')) {
                $separator = str_contains($url, '?') ? '&' : '?';
                return $url . $separator . 'api_key=' . urlencode($storageKey);
            }
            return $url;
        };

        $list = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $ext = strtolower(pathinfo(parse_url($item, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $rawName = basename(parse_url($item, PHP_URL_PATH) ?? '');
                $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', '3gp'])
                    || str_contains(strtolower($item), 'video')
                    || str_contains(strtolower($item), 'vid')
                    || str_contains(strtolower($rawName), 'vid');

                // Auto-generate URL thumbnail dari Go storage server
                $thumbnailUrl = null;
                if (str_contains($item, '/api/v1/files/')) {
                    $thumbnailUrl = preg_replace('#/api/v1/files/([^/]+)/view#', '/api/v1/files/$1/thumbnail', $item);
                    if ($thumbnailUrl === $item && !str_contains($item, '/thumbnail')) {
                        $thumbnailUrl = rtrim($item, '/') . '/thumbnail';
                    }
                } elseif (!$isVideo) {
                    $thumbnailUrl = $item;
                }

                if (empty($rawName) || in_array(strtolower($rawName), ['view', 'download', 'file', 'thumbnail'])) {
                    $rawName = $isVideo ? 'Video Pembelajaran' : 'Foto Kegiatan';
                }

                $list[] = [
                    'url'           => $attachApiKey($item),
                    'view_url'      => $attachApiKey($item),
                    'download_url'  => $attachApiKey($item),
                    'thumbnail_url' => $attachApiKey($thumbnailUrl),
                    'type'          => $isVideo ? 'video' : 'image',
                    'name'          => $rawName,
                    'is_video'      => $isVideo,
                    'is_image'      => !$isVideo,
                ];
            } elseif (is_array($item)) {
                $url = $item['view_url'] ?? ($item['url'] ?? ($item['download_url'] ?? ''));
                $mime = strtolower($item['mime_type'] ?? '');
                $ext = strtolower($item['extension'] ?? pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $rawName = $item['original_name'] ?? ($item['name'] ?? basename(parse_url($url, PHP_URL_PATH) ?? ''));

                $isVideo = (!empty($item['is_video']))
                    || (($item['type'] ?? '') === 'video')
                    || str_starts_with($mime, 'video/')
                    || in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm', '3gp'])
                    || str_contains(strtolower($url), 'video')
                    || str_contains(strtolower($url), 'vid')
                    || str_contains(strtolower($rawName), 'video')
                    || str_contains(strtolower($rawName), 'vid');

                // Dapatkan thumbnail_url dari payload atau generate otomatis dari Go storage server
                $thumbnailUrl = $item['thumbnail_url'] ?? ($item['thumb_url'] ?? null);
                if (empty($thumbnailUrl)) {
                    if (str_contains($url, '/api/v1/files/')) {
                        $thumbnailUrl = preg_replace('#/api/v1/files/([^/]+)/view#', '/api/v1/files/$1/thumbnail', $url);
                        if ($thumbnailUrl === $url && !empty($item['id'])) {
                            $pos = strpos($url, '/api/v1/files/');
                            $base = substr($url, 0, $pos);
                            $thumbnailUrl = $base . '/api/v1/files/' . $item['id'] . '/thumbnail';
                        }
                    } elseif (!$isVideo) {
                        $thumbnailUrl = $url;
                    }
                }

                if (empty($rawName) || in_array(strtolower($rawName), ['view', 'download', 'file', 'thumbnail'])) {
                    $rawName = $isVideo ? 'Video Pembelajaran' : 'Foto Kegiatan';
                }

                $list[] = [
                    'id'            => $item['id'] ?? null,
                    'url'           => $attachApiKey($url),
                    'view_url'      => $attachApiKey($url),
                    'download_url'  => $attachApiKey($item['download_url'] ?? $url),
                    'thumbnail_url' => $attachApiKey($thumbnailUrl),
                    'type'          => $isVideo ? 'video' : 'image',
                    'name'          => $rawName,
                    'size'          => $item['size'] ?? null,
                    'human_size'    => $item['human_size'] ?? null,
                    'mime_type'     => $item['mime_type'] ?? null,
                    'is_video'      => $isVideo,
                    'is_image'      => !$isVideo,
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

    public function jadwalPelajaran()
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
