<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'logo',
        'is_active',
        'wa_enabled',
        'student_limit',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'wa_enabled' => 'boolean',
        'student_limit' => 'integer',
        'settings' => 'array',
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
    public function hasStudentQuota()
    {
        if (empty($this->student_limit) || $this->student_limit <= 0) {
            return true;
        }
        return $this->siswa()->count() < $this->student_limit;
    }
}
