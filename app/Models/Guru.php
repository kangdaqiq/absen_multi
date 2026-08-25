<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'guru';
    public $timestamps = true;
    // timestamps enabled by default
    // const UPDATED_AT = null;  // Column does not exist in DB
    protected $fillable = ['nama', 'nip', 'tgl_lahir', 'no_wa', 'bot_access', 'is_global_report', 'id_finger', 'uid_rfid', 'enroll_status', 'enroll_finger_status', 'created_at', 'updated_at', 'school_id', 'default_shift_id', 'user_id', 'telegram_chat_id', 'last_seen'];

    protected $casts = [
        'bot_access' => 'boolean',
        'is_global_report' => 'boolean',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
