<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'school_id',
        'category',
        'title',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Categories list with labels and target recipient
     */
    public const CATEGORIES = [
        // Siswa
        'checkin_siswa'        => ['label' => 'Absen Masuk (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi dikirim saat siswa scan masuk tepat waktu.'],
        'checkin_ortu'         => ['label' => 'Absen Masuk (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi dikirim ke orang tua saat anak scan masuk.'],
        'late_siswa'           => ['label' => 'Masuk Terlambat (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi dikirim saat siswa scan masuk melebihi batas jam masuk.'],
        'late_ortu'            => ['label' => 'Masuk Terlambat (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi dikirim ke orang tua saat anak terlambat.'],
        'checkout_siswa'       => ['label' => 'Absen Pulang (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi dikirim saat siswa scan pulang.'],
        'checkout_ortu'        => ['label' => 'Absen Pulang (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi dikirim ke orang tua saat anak scan pulang.'],
        'early_checkout_siswa' => ['label' => 'Pulang Cepat (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi dikirim saat siswa scan pulang sebelum jam pulang selesai.'],
        'early_checkout_ortu'  => ['label' => 'Pulang Cepat (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi dikirim ke orang tua saat anak pulang lebih awal.'],
        'izin_siswa'           => ['label' => 'Izin (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi status absensi Izin untuk siswa.'],
        'izin_ortu'            => ['label' => 'Izin (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi konfirmasi Izin ke orang tua.'],
        'sakit_siswa'          => ['label' => 'Sakit (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi status absensi Sakit untuk siswa.'],
        'sakit_ortu'           => ['label' => 'Sakit (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi konfirmasi Sakit ke orang tua.'],
        'alpha_siswa'          => ['label' => 'Alpha / Tidak Hadir (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi harian saat siswa tidak hadir tanpa keterangan.'],
        'alpha_ortu'           => ['label' => 'Alpha / Tidak Hadir (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi ke orang tua saat anak tidak masuk tanpa keterangan.'],
        'bolos_siswa'          => ['label' => 'Auto Bolos (Siswa)', 'target' => 'siswa', 'desc' => 'Notifikasi saat siswa masuk tapi tidak scan absen pulang.'],
        'bolos_ortu'           => ['label' => 'Auto Bolos (ke Orang Tua)', 'target' => 'ortu', 'desc' => 'Notifikasi ke orang tua saat anak terindikasi bolos.'],

        // Guru / Pegawai (Opsional)
        'checkin_guru'         => ['label' => 'Absen Masuk (Guru/Pegawai)', 'target' => 'guru', 'desc' => 'Notifikasi masuk guru/pegawai.'],
        'late_guru'            => ['label' => 'Masuk Terlambat (Guru/Pegawai)', 'target' => 'guru', 'desc' => 'Notifikasi guru/pegawai terlambat.'],
        'checkout_guru'        => ['label' => 'Absen Pulang (Guru/Pegawai)', 'target' => 'guru', 'desc' => 'Notifikasi pulang guru/pegawai.'],
        'early_checkout_guru'  => ['label' => 'Pulang Cepat (Guru/Pegawai)', 'target' => 'guru', 'desc' => 'Notifikasi guru/pegawai pulang cepat.'],
    ];

    /**
     * Supported dynamic placeholders/tags
     */
    public const PLACEHOLDERS = [
        '{nama}'                => 'Nama Siswa / Guru',
        '{nis}'                 => 'NISN / NIP',
        '{kelas}'               => 'Nama Kelas / Jabatan',
        '{tanggal}'             => 'Tanggal Hari Ini (contoh: 02/09/2026)',
        '{jam_masuk}'           => 'Jam Scan Masuk (contoh: 07:15)',
        '{jam_pulang}'          => 'Jam Scan Pulang (contoh: 15:30)',
        '{durasi}'              => 'Durasi di Sekolah (contoh: 7 jam 15 menit)',
        '{durasi_terlambat}'    => 'Keterlambatan (contoh: 15 menit)',
        '{durasi_pulang_cepat}' => 'Selisih Pulang Cepat (contoh: 30 menit)',
        '{status}'              => 'Keterangan Status (Hadir, Izin, Sakit, Terlambat, dll.)',
        '{nama_sekolah}'        => 'Nama Sekolah',
        '{diotorisasi}'         => 'Nama Petugas Pengawas (Absen Pulang)',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Scope for specific school and category
     */
    public function scopeForSchool($query, int $schoolId, string $category)
    {
        return $query->where('school_id', $schoolId)
                     ->where('category', $category)
                     ->where('is_active', true);
    }
}
