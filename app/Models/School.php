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
        'is_active',
        'wa_enabled',
        'bot_enabled',
        'bot_user_limit',
        'student_limit',
        'teacher_limit',
        'history_quota_months',
        'settings',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'wa_enabled'           => 'boolean',
        'bot_enabled'          => 'boolean',
        'bot_user_limit'       => 'integer',
        'student_limit'        => 'integer',
        'teacher_limit'        => 'integer',
        'history_quota_months' => 'integer',
        'settings'             => 'array',
    ];

    /**
     * Get wa_enabled attribute.
     * Always returns true in self_hosted mode.
     */
    public function getWaEnabledAttribute($value): bool
    {
        if (config('app.mode', 'hosted') === 'self_hosted') {
            return true;
        }
        return (bool) $value;
    }

    /**
     * Get bot_enabled attribute.
     * Always returns true in self_hosted mode.
     */
    public function getBotEnabledAttribute($value): bool
    {
        if (config('app.mode', 'hosted') === 'self_hosted') {
            return true;
        }
        return (bool) $value;
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
     * Get all admins for this school
     */
    public function admins()
    {
        return $this->users()->where('role', 'admin');
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
}
