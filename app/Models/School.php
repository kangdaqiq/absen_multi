<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name',
        'type',
        'code',
        'address',
        'phone',
        'operator_phone',
        'email',
        'logo',
        'domain',
        'is_active',
        'photo_enabled',
        'face_enabled',
        'dok_kbm_enabled',
        'storage_quota_mb',
        'storage_api_key',
        'storage_user_id',
        'wa_enabled',
        'bot_enabled',
        'bot_user_limit',
        'student_limit',
        'teacher_limit',
        'history_quota_months',
        'settings',
        'expired_at',
        'telegram_bot_token',
        'telegram_enabled',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'photo_enabled'        => 'boolean',
        'face_enabled'         => 'boolean',
        'dok_kbm_enabled'      => 'boolean',
        'storage_quota_mb'     => 'integer',
        'wa_enabled'           => 'boolean',
        'bot_enabled'          => 'boolean',
        'bot_user_limit'       => 'integer',
        'student_limit'        => 'integer',
        'teacher_limit'        => 'integer',
        'history_quota_months' => 'integer',
        'settings'             => 'array',
        'expired_at'           => 'datetime',
        'telegram_enabled'     => 'boolean',
    ];

    protected $appends = [
        'logo_url',
        'storage_quota_human',
    ];

    /**
     * Human-readable storage quota (e.g. 1000 MB, 2 GB, Unlimited)
     */
    public function getStorageQuotaHumanAttribute(): string
    {
        $mb = $this->storage_quota_mb;
        if ($mb === null || $mb <= 0) {
            return 'Unlimited';
        }
        if ($mb >= 1024) {
            return round($mb / 1024, 2) . ' GB';
        }
        return $mb . ' MB';
    }

    /**
     * API Key yang berlaku untuk upload ke storage server
     */
    public function getEffectiveStorageApiKey(): string
    {
        return $this->storage_api_key ?: config('services.storage_server.key') ?: 'android-storage-secret-key';
    }

    /**
     * Accessor for full logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        $logo = $this->logo;

        if (empty($logo)) {
            $logo = Setting::where('school_id', $this->id)->where('setting_key', 'logo_filename')->value('setting_value');
        }

        if (empty($logo)) {
            $logo = Setting::where('school_id', 0)->where('setting_key', 'logo_filename')->value('setting_value');
        }

        if (empty($logo)) {
            return asset('images/logo/logo.svg');
        }

        if (\Illuminate\Support\Str::startsWith($logo, ['http://', 'https://'])) {
            return $logo;
        }

        $cleanPath = ltrim(str_replace(['\\', '/'], '/', $logo), '/');

        if (file_exists(public_path('storage/' . $cleanPath))) {
            return asset('storage/' . $cleanPath);
        }

        if (file_exists(public_path($cleanPath))) {
            return asset($cleanPath);
        }

        if (\Illuminate\Support\Str::startsWith($cleanPath, ['schools/', 'settings/'])) {
            return asset('storage/' . $cleanPath);
        }

        if (file_exists(public_path('images/logo/' . $cleanPath))) {
            return asset('images/logo/' . $cleanPath);
        }

        return asset('storage/' . $cleanPath);
    }

    /**
     * Get all users for this school
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all students for this school
     */
    public function siswa()
    {
        return $this->hasMany(Siswa::class);
    }

    /**
     * Get all teachers for this school
     */
    public function guru()
    {
        return $this->hasMany(Guru::class);
    }

    /**
     * Get all classes for this school
     */
    public function kelas()
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Get all jurusan for this school
     */
    public function jurusan()
    {
        return $this->hasMany(Jurusan::class);
    }

    /**
     * Get all admins for this school
     */
    public function admins()
    {
        return $this->users()->where('role', 'admin');
    }

    /**
     * Get all subscriptions for this school
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if school has student quota available.
     * Returns true if unlimited (null/0) or count < limit.
     */
    public function hasStudentQuota(): bool
    {
        if (empty($this->student_limit) || $this->student_limit <= 0) {
            return true;
        }
        return $this->siswa()->count() < $this->student_limit;
    }

    /**
     * Check if school has teacher quota available.
     * Returns true if unlimited (null/0) or count < limit.
     */
    public function hasTeacherQuota(): bool
    {
        if (empty($this->teacher_limit) || $this->teacher_limit <= 0) {
            return true;
        }
        return $this->guru()->count() < $this->teacher_limit;
    }

    /**
     * Check if school's bot user quota is available.
     * Returns true if unlimited (0) or bot_access_count < bot_user_limit.
     */
    public function hasBotQuota(): bool
    {
        if (empty($this->bot_user_limit) || $this->bot_user_limit <= 0) {
            return true;
        }
        return $this->guru()->where('bot_access', true)->count() < $this->bot_user_limit;
    }

    /**
     * Count how many teachers currently have bot access.
     */
    public function botAccessCount(): int
    {
        return $this->guru()->where('bot_access', true)->count();
    }

    // ── Type helpers ───────────────────────────────────────────────────────

    /** Returns true if this is a school (default). */
    public function isSchool(): bool
    {
        return $this->type === 'school' || empty($this->type);
    }

    /** Returns true if this is an office/corporate tenant. */
    public function isOffice(): bool
    {
        return $this->type === 'office';
    }

    /** Returns true if this is a pesantren tenant. */
    public function isPesantren(): bool
    {
        return $this->type === 'pesantren';
    }

    /**
     * Dynamic label for "Guru" depending on type.
     * Usage: $school->employeeLabel()  → 'Guru' or 'Karyawan'
     */
    public function employeeLabel(): string
    {
        return $this->isOffice() ? 'Karyawan' : 'Guru';
    }

    /**
     * Dynamic label for NIP depending on type.
     * Usage: $school->nipLabel()  → 'NIP' or 'ID Pegawai'
     */
    public function nipLabel(): string
    {
        return $this->isOffice() ? 'ID Pegawai' : 'NIP';
    }

    // ── Grace Period & WhatsApp Status ────────────────────────────────────

    public const GRACE_PERIOD_DAYS = 3;

    /**
     * Cek apakah masa berlaku sekolah masih aktif atau dalam masa tenggang (grace period <= 3 hari).
     * Jika expired_at bernilai null, berarti sekolah aktif tanpa batas waktu (lifetime).
     */
    public function isWithinGracePeriod(): bool
    {
        if (!$this->expired_at) {
            return true;
        }

        // Aktif normal jika expired_at belum lewat
        if ($this->expired_at->isFuture() || $this->expired_at->isToday()) {
            return true;
        }

        // Dalam masa tenggang jika belum lewat 3 hari setelah tanggal expired_at (sampai 23:59:59 hari ke-3)
        return now()->lte($this->expired_at->copy()->addDays(self::GRACE_PERIOD_DAYS)->endOfDay());
    }

    /**
     * Cek apakah sekolah sudah kedaluwarsa LEBIH DARI 3 HARI.
     */
    public function isExpiredOverGracePeriod(): bool
    {
        return !$this->isWithinGracePeriod();
    }

    /**
     * Cek apakah fitur WhatsApp sekolah aktif dan berhak beroperasi mengirim pesan:
     * 1. wa_enabled harus bernilai true
     * 2. Belum kedaluwarsa lebih dari 3 hari (toleransi/grace period 3 hari)
     */
    public function isWaActive(): bool
    {
        if (!$this->wa_enabled) {
            return false;
        }

        return $this->isWithinGracePeriod();
    }
}
