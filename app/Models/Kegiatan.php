<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    protected $table = 'kegiatans';
    public $timestamps = true;

    protected $fillable = [
        'school_id',
        'nama_kegiatan',
        'deskripsi',
        'tanggal_mulai',
        'frekuensi',
        'target_type',
        'kategori',
        'pembina_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'uid_kartu',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'tanggal_mulai'   => 'date',
        'hari'            => 'array',
    ];

    public function pembina()
    {
        return $this->belongsTo(Guru::class, 'pembina_id');
    }

    public function kelas()
    {
        return $this->belongsToMany(Kelas::class, 'kegiatan_kelas', 'kegiatan_id', 'kelas_id');
    }

    public function siswas()
    {
        return $this->belongsToMany(Siswa::class, 'kegiatan_siswa', 'kegiatan_id', 'student_id');
    }

    /**
     * Cek apakah siswa berhak / terdaftar mengikuti kegiatan ini.
     */
    public function isStudentEligible($student): bool
    {
        if (is_numeric($student)) {
            $student = Siswa::find($student);
        }

        if (!$student || $student->school_id != $this->school_id) {
            return false;
        }

        if ($this->target_type === 'kelas') {
            $targetKelasIds = $this->kelas()->pluck('kelas.id')->toArray();
            return in_array($student->kelas_id, $targetKelasIds);
        }

        if ($this->target_type === 'siswa') {
            $targetStudentIds = $this->siswas()->pluck('siswa.id')->toArray();
            return in_array($student->id, $targetStudentIds);
        }

        // 'all' or default
        return true;
    }

    /**
     * Query builder untuk mengambil seluruh siswa target kegiatan ini.
     */
    public function getTargetStudentsQuery()
    {
        $query = Siswa::where('school_id', $this->school_id);

        if ($this->target_type === 'kelas') {
            $kelasIds = $this->kelas()->pluck('kelas.id')->toArray();
            return $query->whereIn('kelas_id', $kelasIds);
        }

        if ($this->target_type === 'siswa') {
            $siswaIds = $this->siswas()->pluck('siswa.id')->toArray();
            return $query->whereIn('id', $siswaIds);
        }

        return $query;
    }

    /**
     * Label cakupan peserta untuk UI.
     */
    public function getTargetScopeLabelAttribute(): string
    {
        if ($this->target_type === 'kelas') {
            $count = $this->kelas()->count();
            if ($count === 0) return 'Kelas (Belum Dipilih)';
            if ($count === 1) {
                return 'Kelas ' . ($this->kelas->first()->nama_kelas ?? '');
            }
            return $count . ' Kelas Terpilih';
        }

        if ($this->target_type === 'siswa') {
            $count = $this->siswas()->count();
            return $count . ' Siswa Terpilih';
        }

        return 'Semua Siswa';
    }

    /**
     * Cek apakah kegiatan sedang berjalan berdasarkan jadwal waktu hari ini.
     */
    public function isScheduledNow($now = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->jam_mulai || !$this->jam_selesai) {
            return false;
        }

        $now = $now ? \Carbon\Carbon::parse($now) : now();
        $nowTimeStr = $now->format('H:i:s');
        $todayStr = $now->format('Y-m-d');
        
        $rawTgl = $this->getRawOriginal('tanggal_mulai');
        $startDate = \Carbon\Carbon::parse($rawTgl ?: $this->tanggal_mulai);
        $startDateStr = $startDate->format('Y-m-d');

        if ($todayStr < $startDateStr) {
            return false;
        }

        // Check frekuensi
        if ($this->frekuensi === 'sekali') {
            if ($todayStr !== $startDateStr) {
                return false;
            }
        } elseif ($this->frekuensi === 'harian') {
            if (!empty($this->hari) && is_array($this->hari) && count($this->hari) < 7) {
                $dayIndex = $now->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
                $hariList = array_map('intval', $this->hari);
                if (!in_array($dayIndex, $hariList)) {
                    return false;
                }
            }
        } elseif ($this->frekuensi === 'mingguan') {
            if ($now->dayOfWeekIso !== $startDate->dayOfWeekIso) {
                return false;
            }
        } elseif ($this->frekuensi === 'bulanan') {
            if ($now->day !== $startDate->day) {
                return false;
            }
        }

        $jamMulaiStr = strlen($this->jam_mulai) === 5 ? $this->jam_mulai . ':00' : $this->jam_mulai;
        $jamSelesaiStr = strlen($this->jam_selesai) === 5 ? $this->jam_selesai . ':59' : $this->jam_selesai;

        return $nowTimeStr >= $jamMulaiStr && $nowTimeStr <= $jamSelesaiStr;
    }

    /**
     * Accessor untuk teks frekuensi & hari kegiatan.
     */
    public function getFormattedHariAttribute(): string
    {
        if ($this->frekuensi !== 'harian') {
            return match($this->frekuensi) {
                'sekali' => 'Sekali (Insidental)',
                'mingguan' => 'Mingguan',
                'bulanan' => 'Bulanan',
                default => ucfirst($this->frekuensi ?? 'Harian')
            };
        }

        $hari = $this->hari;
        if (empty($hari) || !is_array($hari) || count($hari) === 7) {
            return 'Harian (Setiap Hari)';
        }

        $hariInt = array_map('intval', $hari);
        sort($hariInt);

        if ($hariInt === [1, 2, 3, 4, 5]) {
            return 'Harian (Senin - Jumat)';
        }
        if ($hariInt === [1, 2, 3, 4, 5, 6]) {
            return 'Harian (Senin - Sabtu)';
        }

        $map = [
            1 => 'Sen',
            2 => 'Sel',
            3 => 'Rab',
            4 => 'Kam',
            5 => 'Jum',
            6 => 'Sab',
            7 => 'Min',
        ];

        $names = array_map(fn($d) => $map[$d] ?? '', $hariInt);
        return 'Harian (' . implode(', ', array_filter($names)) . ')';
    }


    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function attendances()
    {
        return $this->hasMany(KegiatanAttendance::class, 'kegiatan_id');
    }

    public function sessions()
    {
        return $this->hasMany(KegiatanSession::class, 'kegiatan_id');
    }

    public function activeSession()
    {
        return $this->hasOne(KegiatanSession::class, 'kegiatan_id')
            ->where('expires_at', '>=', now());
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
