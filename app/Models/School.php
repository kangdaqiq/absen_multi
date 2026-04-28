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
        'student_limit',
        'teacher_limit',
        'history_quota_months',
        'settings',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'wa_enabled'           => 'boolean',
        'student_limit'        => 'integer',
        'teacher_limit'        => 'integer',
        'history_quota_months' => 'integer',
        'settings'             => 'array',
    ];

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
