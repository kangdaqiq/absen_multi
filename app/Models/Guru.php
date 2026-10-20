<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'guru';
    public $timestamps = true;
    // timestamps enabled by default
    // const UPDATED_AT = null;  // Column does not exist in DB

    protected static function booted()
    {
        static::deleting(function ($guru) {
            if ($guru->foto) {
                self::deletePhotoFile($guru->foto);
            }
            $fingerIds = $guru->fingerprints()->pluck('finger_id')->toArray();
            if ($guru->id_finger && !in_array($guru->id_finger, $fingerIds)) {
                $fingerIds[] = (int)$guru->id_finger;
            }
            if (!empty($fingerIds) && $guru->school_id) {
                Device::queueFingerDeletion($guru->school_id, $fingerIds);
            }
            $guru->fingerprints()->delete();
        });
    }

    protected $fillable = [
        'nama',
        'nip',
        'tgl_lahir',
        'no_wa',
        'foto',
        'bot_access',
        'is_global_report',
        'is_teacher_report',
        'id_finger',
        'uid_rfid',
        'enroll_status',
        'enroll_finger_status',
        'created_at',
        'updated_at',
        'school_id',
        'default_shift_id',
        'user_id',
        'telegram_chat_id',
        'last_seen'
    ];

    protected $appends = ['foto_url', 'username', 'public_identifier'];

    public function getUsernameAttribute(): ?string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->username;
        }
        if ($this->user_id) {
            return $this->user?->username;
        }
        return null;
    }

    public function getPublicIdentifierAttribute(): string
    {
        return $this->username ?: (string)$this->id;
    }

    public function getFotoUrlAttribute(): string
    {
        if (!empty($this->foto)) {
            if (\Illuminate\Support\Str::startsWith($this->foto, ['http://', 'https://'])) {
                return $this->foto;
            }

            if (file_exists(public_path('storage/' . $this->foto))) {
                return asset('storage/' . $this->foto);
            }

            if (file_exists(public_path($this->foto))) {
                return asset($this->foto);
            }

            $diskPath = self::getPhotoDiskPath($this->foto);
            if ($diskPath && file_exists($diskPath)) {
                return asset('storage/' . ltrim(str_replace(['\\', '/'], '/', $this->foto), '/'));
            }
        }

        $name = urlencode($this->nama ?? 'Guru');
        return "https://ui-avatars.com/api/?name={$name}&background=10b981&color=fff&size=256&bold=true";
    }

    public static function getPhotoDiskPath(?string $photo): ?string
    {
        if (empty($photo)) {
            return null;
        }

        $cleanPhoto = str_replace(['..', "\0"], '', $photo);
        $cleanPhoto = ltrim($cleanPhoto, '/\\');
        $baseName = basename($cleanPhoto);

        $storageDirect = storage_path('app/public/' . $cleanPhoto);
        if (file_exists($storageDirect)) {
            return $storageDirect;
        }

        $storageBase = storage_path('app/public/guru/' . $baseName);
        if (file_exists($storageBase)) {
            return $storageBase;
        }

        $publicDirect = public_path('storage/' . $cleanPhoto);
        if (file_exists($publicDirect)) {
            return $publicDirect;
        }

        return null;
    }

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

    public function faceData()
    {
        return $this->hasOne(UserFaceData::class, 'guru_id');
    }

    public function getHasFaceRegisteredAttribute(): bool
    {
        if ($this->relationLoaded('faceData') && $this->faceData) {
            return true;
        }
        if ($this->relationLoaded('user') && $this->user && $this->user->relationLoaded('faceData') && $this->user->faceData) {
            return true;
        }
        return $this->faceData()->exists() || ($this->user_id && UserFaceData::where('user_id', $this->user_id)->exists());
    }

    protected $casts = [
        'bot_access' => 'boolean',
        'is_global_report' => 'boolean',
        'is_teacher_report' => 'boolean',
        'last_seen' => 'datetime',
    ];

    public function defaultShift()
    {
        return $this->belongsTo(Shift::class, 'default_shift_id');
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'guru_shift_assignments', 'guru_id', 'shift_id')->distinct();
    }

    public function shiftAssignments()
    {
        return $this->hasMany(GuruShiftAssignment::class, 'guru_id');
    }

    /**
     * Cari shift aktif untuk guru pada tanggal/hari tertentu
     */
    public function getShiftForDate($date = null): ?Shift
    {
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now();
        $dayIndex = $targetDate->dayOfWeekIso; // 1 (Mon) - 7 (Sun)

        // 1. Cek shift dari daftar penugasan guru (plotting shift)
        $assignedShifts = $this->shifts()->where('is_active', true)->get();
        foreach ($assignedShifts as $s) {
            if ($s->isActiveOnDay($dayIndex)) {
                return $s;
            }
        }

        // 2. Cek default shift profil jika belum ada di tabel penugasan
        if ($this->default_shift_id) {
            $default = $this->defaultShift;
            if ($default && $default->is_active && $default->isActiveOnDay($dayIndex)) {
                return $default;
            }
        }

        // 3. Fallback: Shift aktif di sekolah jika guru belum di-setting shift khusus
        if ($this->school_id) {
            $schoolShifts = Shift::where('school_id', $this->school_id)
                ->where('is_active', true)
                ->orderBy('jam_masuk', 'asc')
                ->get();
            foreach ($schoolShifts as $s) {
                if ($s->isActiveOnDay($dayIndex)) {
                    return $s;
                }
            }
        }

        return null;
    }

    public function isWithinLastSeen($hours = 72): bool
    {
        if (!$this->last_seen) {
            return false;
        }
        return \Carbon\Carbon::parse($this->last_seen)->gte(now()->subHours($hours));
    }

    public function fingerprints()
    {
        return $this->hasMany(GuruFingerprint::class, 'guru_id');
    }

    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class, 'guru_id');
    }

    public function absensi()
    {
        return $this->hasMany(AbsensiGuru::class, 'guru_id');
    }

    public function kelas()
    {
        return $this->hasOne(Kelas::class, 'wali_kelas_id');
    }

    public function kelasSecondary()
    {
        return $this->hasOne(Kelas::class, 'wali_kelas_2_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
