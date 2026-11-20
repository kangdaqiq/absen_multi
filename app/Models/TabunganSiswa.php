<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TabunganSiswa extends Model
{
    use HasFactory;

    protected $table = 'tabungan_siswas';

    protected $fillable = [
        'school_id',
        'student_id',
        'recorded_by',
        'code',
        'tanggal',
        'waktu',
        'jenis',
        'nominal',
        'saldo_sebelum',
        'saldo_sesudah',
        'metode',
        'keterangan',
    ];

    protected $casts = [
        'tanggal'       => 'date',
        'nominal'       => 'integer',
        'saldo_sebelum' => 'integer',
        'saldo_sesudah' => 'integer',
    ];

    protected $appends = [
        'jenis_label',
        'nominal_formatted',
        'saldo_sebelum_formatted',
        'saldo_sesudah_formatted',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'setor' => 'Setor Tunai',
            'tarik' => 'Tarik Tunai',
            default => ucfirst((string) $this->jenis),
        };
    }

    public function getNominalFormattedAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->nominal, 0, ',', '.');
    }

    public function getSaldoSebelumFormattedAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->saldo_sebelum, 0, ',', '.');
    }

    public function getSaldoSesudahFormattedAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->saldo_sesudah, 0, ',', '.');
    }

    public static function generateCode(): string
    {
        do {
            $code = 'TAB-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Rekalkulasi penuh riwayat saldo berjalan (saldo_sebelum & saldo_sesudah) untuk seorang siswa
     * serta memperbarui siswa.saldo_tabungan secara atomik.
     */
    public static function recalculateStudentBalance(int $studentId): int
    {
        return DB::transaction(function () use ($studentId) {
            $student = Siswa::lockForUpdate()->find($studentId);
            if (!$student) {
                return 0;
            }

            $transactions = static::where('student_id', $studentId)
                ->orderBy('tanggal', 'asc')
                ->orderBy('waktu', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $runningBalance = 0;
            foreach ($transactions as $trx) {
                $before = $runningBalance;
                if ($trx->jenis === 'setor') {
                    $runningBalance += (int) $trx->nominal;
                } else {
                    $runningBalance -= (int) $trx->nominal;
                }

                if ((int) $trx->saldo_sebelum !== $before || (int) $trx->saldo_sesudah !== $runningBalance) {
                    $trx->updateQuietly([
                        'saldo_sebelum' => $before,
                        'saldo_sesudah' => $runningBalance,
                    ]);
                }
            }

            $student->saldo_tabungan = $runningBalance;
            $student->saveQuietly();

            return $runningBalance;
        });
    }
}
