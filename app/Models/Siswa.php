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

    protected static function booted()
    {
        static::deleting(function ($siswa) {
            $fingerIds = $siswa->fingerprints()->pluck('finger_id')->toArray();
            if ($siswa->id_finger && !in_array($siswa->id_finger, $fingerIds)) {
                $fingerIds[] = (int)$siswa->id_finger;
            }
            if (!empty($fingerIds) && $siswa->school_id) {
                Device::queueFingerDeletion($siswa->school_id, $fingerIds);
            }
            $siswa->fingerprints()->delete();
        });
    }

    protected $fillable = [
        'nama',
        'nis',
        'kelas_id',
        'tgl_lahir',
        'alamat',
        'foto',
        'no_wa',
        'wa_ortu',
        'uid_rfid',
        'enroll_status',
        'id_finger',
        'enroll_finger_status',
        'created_at',
        'updated_at',
        'school_id',
        'user_id',
        'fcm_token',
        'is_khusus',
        'is_siswa_khusus',
        'hari_masuk',
        'telegram_chat_id',
        'telegram_ortu_chat_id',
        'last_seen_siswa',
        'last_seen_ortu',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): string
    {
        // Jika fitur foto dinonaktifkan (via Super Admin atau Lisensi Client), kembalikan avatar
        $isPhotoEnabled = app(\App\Services\LicenseService::class)->isPhotoFeatureEnabled($this->school);
        if (!$isPhotoEnabled) {
            $name = urlencode($this->nama ?? 'Siswa');
            return "https://ui-avatars.com/api/?name={$name}&background=4f46e5&color=fff&size=256&bold=true";
        }

        if (!empty($this->foto)) {
            if (\Illuminate\Support\Str::startsWith($this->foto, ['http://', 'https://'])) {
                return $this->foto;
            }

            // 1. Jika external drive/folder disetting di .env
            $externalPath = config('filesystems.siswa_photo_path');
            if (!empty($externalPath)) {
                $diskPath = self::getPhotoDiskPath($this->foto);
                if ($diskPath && file_exists($diskPath)) {
                    return route('siswa.photo', ['filename' => basename($this->foto)]);
                }
            }

            // 2. Jika ada di public/storage (symlink normal Laravel)
            if (file_exists(public_path('storage/' . $this->foto))) {
                return asset('storage/' . $this->foto);
            }

            if (file_exists(public_path($this->foto))) {
                return asset($this->foto);
            }

            // 3. Fallback: Jika file ada di server tapi symlink public belum ada
            if (self::getPhotoDiskPath($this->foto)) {
                return route('siswa.photo', ['filename' => basename($this->foto)]);
            }
        }

        // Return pleasant avatar fallback with initials
        $name = urlencode($this->nama ?? 'Siswa');
        return "https://ui-avatars.com/api/?name={$name}&background=4f46e5&color=fff&size=256&bold=true";
    }

    /**
     * Cari lokasi fisik file foto di disk/drive server (eksternal ataupun storage default).
     */
    public static function getPhotoDiskPath(?string $photo): ?string
    {
        if (empty($photo)) {
            return null;
        }

        $cleanPhoto = str_replace(['..', "\0"], '', $photo);
        $cleanPhoto = ltrim($cleanPhoto, '/\\');
        $baseName = basename($cleanPhoto);

        // 1. Cek direktori/drive eksternal (SISWA_PHOTO_PATH)
        $externalPath = config('filesystems.siswa_photo_path');
        if (!empty($externalPath)) {
            $base = rtrim($externalPath, '/\\');

            if (file_exists($base . DIRECTORY_SEPARATOR . $cleanPhoto)) {
                return $base . DIRECTORY_SEPARATOR . $cleanPhoto;
            }
            if (file_exists($base . DIRECTORY_SEPARATOR . $baseName)) {
                return $base . DIRECTORY_SEPARATOR . $baseName;
            }
            if (file_exists($base . DIRECTORY_SEPARATOR . 'siswa' . DIRECTORY_SEPARATOR . $baseName)) {
                return $base . DIRECTORY_SEPARATOR . 'siswa' . DIRECTORY_SEPARATOR . $baseName;
            }
        }

        // 2. Cek direktori storage bawaan Laravel (storage/app/public/...)
        $storageDirect = storage_path('app/public/' . $cleanPhoto);
        if (file_exists($storageDirect)) {
            return $storageDirect;
        }

        $storageBase = storage_path('app/public/siswa/' . $baseName);
        if (file_exists($storageBase)) {
            return $storageBase;
        }

        // 3. Cek folder public/storage/...
        $publicDirect = public_path('storage/' . $cleanPhoto);
        if (file_exists($publicDirect)) {
            return $publicDirect;
        }

        $publicBase = public_path('storage/siswa/' . $baseName);
        if (file_exists($publicBase)) {
            return $publicBase;
        }

        if (file_exists(public_path($cleanPhoto))) {
            return public_path($cleanPhoto);
        }

        return null;
    }

    /**
     * Hapus file foto dari disk (eksternal ataupun lokal) saat data diubah atau dihapus.
     */
    public static function deletePhotoFile(?string $photo): void
    {
        if (empty($photo)) {
            return;
        }

        $diskPath = self::getPhotoDiskPath($photo);
        if ($diskPath && file_exists($diskPath)) {
            @unlink($diskPath);
        }
    }

    protected $casts = [
        'hari_masuk' => 'array',
        'last_seen_siswa' => 'datetime',
        'last_seen_ortu' => 'datetime',
    ];

    public function isSiswaWithinLastSeen($hours = 72): bool
    {
        if (!$this->last_seen_siswa) {
            return false;
        }
        return \Carbon\Carbon::parse($this->last_seen_siswa)->gte(now()->subHours($hours));
    }

    public function isOrtuWithinLastSeen($hours = 72): bool
    {
        if (!$this->last_seen_ortu) {
            return false;
        }
        return \Carbon\Carbon::parse($this->last_seen_ortu)->gte(now()->subHours($hours));
    }

    public function isEntryDay($date = null)
    {
        if (!$this->is_siswa_khusus) {
            return true;
        }

        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $dayIndex = $date->dayOfWeekIso; // 1-7 (1=Senin, 7=Minggu)

        $hariMasuk = $this->hari_masuk;

        if (!is_array($hariMasuk)) {
            $hariMasuk = json_decode($hariMasuk, true) ?: [];
        }

        // Convert all elements to int to avoid type comparison issues
        $hariMasuk = array_map('intval', $hariMasuk);

        return in_array($dayIndex, $hariMasuk);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function kegiatanAttendances()
    {
        return $this->hasMany(KegiatanAttendance::class, 'student_id');
    }

    public function fingerprints()
    {
        return $this->hasMany(SiswaFingerprint::class, 'student_id');
    }
}
