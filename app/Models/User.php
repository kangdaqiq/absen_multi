<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    // Enable timestamps as table has updated_at/created_at
    public $timestamps = true;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password_hash',
        'plain_password',
        'role',
        'school_id',
        'fcm_token',
    ];

    protected $hidden = [
        'password_hash',
        'plain_password',
    ];

    /**
     * Kredensial password yang dapat ditampilkan untuk kebutuhan cetak/export akun
     */
    public function getDisplayPasswordAttribute()
    {
        if (!empty($this->plain_password)) {
            return $this->plain_password;
        }

        // Cek fallback default password yang sering digunakan sistem
        if (\Illuminate\Support\Facades\Hash::check('Demo123@', $this->password_hash)) {
            return 'Demo123@';
        }
        if (\Illuminate\Support\Facades\Hash::check('password', $this->password_hash)) {
            return 'password';
        }
        if (\Illuminate\Support\Facades\Hash::check('12345678', $this->password_hash)) {
            return '12345678';
        }
        if (\Illuminate\Support\Facades\Hash::check('superadmin123', $this->password_hash)) {
            return 'superadmin123';
        }

        return '••••••••';
    }

    /**
     * Label representasi role pengguna yang rapi
     */
    public function getRoleLabelAttribute()
    {
        return match ($this->role) {
            'super_admin'    => 'Super Admin',
            'admin'          => 'Administrator',
            'waka_kurikulum' => 'Waka Kurikulum',
            'wali_kelas'     => 'Wali Kelas',
            'guru', 'teacher' => 'Guru Pengajar',
            'siswa', 'student' => 'Siswa',
            default          => ucfirst(str_replace('_', ' ', $this->role ?? 'User')),
        };
    }

    /**
     * Overrides for Auth
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // Map 'name' to 'full_name' accessor if needed
    public function getNameAttribute()
    {
        return $this->full_name;
    }

    public function student()
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'user_id');
    }

    public function faceData()
    {
        return $this->hasOne(UserFaceData::class, 'user_id');
    }

    /**
     * Get the school that owns the user
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Scope a query to only include users from a specific school
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * Check if user is guru / teacher
     */
    public function isGuru()
    {
        return in_array($this->role, ['guru', 'teacher']);
    }

    public function isTeacher()
    {
        return $this->isGuru();
    }

    public function isWaliKelas()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            return false;
        }

        if ($this->role === 'wali_kelas') {
            return true;
        }

        if ($this->isGuru() && $this->guru) {
            return \App\Models\Kelas::where('school_id', $this->school_id)
                ->where(function ($q) {
                    $q->where('wali_kelas_id', $this->guru->id)
                      ->orWhere('wali_kelas_2_id', $this->guru->id);
                })->exists();
        }

        return false;
    }

    /**
     * Get array of class IDs managed by this wali kelas / guru (supports 1, 2, 3, 4+ classes)
     *
     * @return array
     */
    public function getManagedKelasIds()
    {
        $guru = $this->guru;
        if (!$guru) {
            return [];
        }

        return \App\Models\Kelas::where('school_id', $this->school_id)
            ->where(function ($q) use ($guru) {
                $q->where('wali_kelas_id', $guru->id)
                  ->orWhere('wali_kelas_2_id', $guru->id);
            })->pluck('id')->toArray();
    }

    public function isWakaKurikulum()
    {
        return $this->role === 'waka_kurikulum';
    }
}
