<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudentLeave extends Model
{
    use HasFactory;

    protected $table = 'student_leaves';

    protected $fillable = [
        'school_id',
        'student_id',
        'code',
        'jenis',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'bukti_foto',
        'pengaju',
        'nama_pengaju',
        'no_wa_pengaju',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'approved_at'     => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function getDurasiHariAttribute(): int
    {
        if (!$this->tanggal_mulai || !$this->tanggal_selesai) {
            return 1;
        }
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'sakit'      => 'Sakit',
            'izin'       => 'Izin',
            'dispensasi' => 'Dispensasi',
            default      => ucfirst($this->jenis),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'  => '<span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400"><i class="fas fa-clock text-[10px]"></i> Menunggu</span>',
            'approved' => '<span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-400"><i class="fas fa-check-circle text-[10px]"></i> Disetujui</span>',
            'rejected' => '<span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400"><i class="fas fa-times-circle text-[10px]"></i> Ditolak</span>',
            default    => '<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">' . ucfirst($this->status) . '</span>',
        };
    }

    public static function generateCode(): string
    {
        do {
            $code = 'IZIN-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
