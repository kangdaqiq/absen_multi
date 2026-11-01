<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AbsensiGuru;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Setting;
use App\Models\GateCard;
use App\Models\TeacherCheckoutSession;
use App\Models\Kegiatan;
use App\Models\KegiatanAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceCoreService
{
    /**
     * Batas minimal (menit) antara absen masuk dan pulang pada mode 'auto'.
     * Mencegah siswa/guru yang masih berdiri di depan scanner langsung tercatat PULANG.
     */
    public const MIN_CHECKOUT_GAP_MINUTES = 10;

    protected WhatsAppService $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Dapatkan timezone sekolah atau default sistem
     */
    public function getSchoolTimezone($schoolId): string
    {
        return Setting::where('school_id', $schoolId)
            ->where('setting_key', 'timezone')
            ->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
    }

    /**
     * Cek apakah waktu scan kepulangan terlalu dekat dengan waktu masuk
     */
    public function isWithinCheckoutGap($tanggal, $jamMasuk, Carbon $now): bool
    {
        if (empty($jamMasuk)) {
            return false;
        }

        try {
            $tanggalStr = $tanggal ? Carbon::parse($tanggal)->format('Y-m-d') : $now->format('Y-m-d');
            $jamStr = $jamMasuk instanceof \DateTimeInterface ? $jamMasuk->format('H:i:s') : (string) $jamMasuk;
            $masukAt = Carbon::parse($tanggalStr . ' ' . $jamStr, $now->getTimezone());
            return abs($masukAt->diffInSeconds($now, false)) < (self::MIN_CHECKOUT_GAP_MINUTES * 60);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Label manusiawi untuk kanal absensi
     */
    private function getChannelLabel(string $channel): string
    {
        return match ($channel) {
            'web_face'    => 'Face Kiosk Scanner',
            'web_usb'     => 'USB RFID Scanner',
            'rfid'        => 'Mesin RFID',
            'fingerprint' => 'Mesin Fingerprint',
            'mobile'      => 'Aplikasi Mobile',
            default       => ucfirst(str_replace('_', ' ', $channel)),
        };
    }

    /**
     * =========================================================================
     * INTI LOGIKA PRESENSI SISWA (MASUK / PULANG / KEGIATAN)
     * =========================================================================
     *
     * @param Siswa $siswa Model siswa yang diautentikasi
     * @param array $options Opsi tambahan:
     *   - school_id: ?int
     *   - now: ?Carbon
     *   - mode: 'auto'|'masuk'|'pulang' (default: 'auto')
     *   - channel: 'web_face'|'web_usb'|'rfid'|'fingerprint'|'mobile' (default: 'web_face')
     *   - similarity: ?float (untuk face recognition)
     *   - snapshot_path: ?string
     *   - uid: ?string
     * @return array Response seragam untuk controller
     */
    public function recordStudentAttendance(Siswa $siswa, array $options = []): array
    {
        $schoolId = $options['school_id'] ?? $siswa->school_id;
        $schoolTz = $this->getSchoolTimezone($schoolId);
        $now = isset($options['now']) && $options['now'] instanceof Carbon
            ? $options['now']->copy()->setTimezone($schoolTz)
            : Carbon::now($schoolTz);
        $today = $now->format('Y-m-d');

        $reqMode = $options['mode'] ?? 'auto';
        $channel = $options['channel'] ?? 'web_face';
        $similarity = isset($options['similarity']) ? (float) $options['similarity'] : null;
        $snapshotPath = $options['snapshot_path'] ?? null;
        $channelLabel = $this->getChannelLabel($channel);
        $uid = $options['uid'] ?? ($siswa->uid_rfid ?? null);

        $siswaSchoolId = $siswa->school_id ?: $schoolId;

        // 1. Cek Jadwal Sekolah Hari Ini
        $indexHari = (int) $now->format('N');
        $jadwal = Jadwal::where('index_hari', $indexHari)
            ->where('is_active', 1)
            ->when($siswaSchoolId, fn($q) => $q->where('school_id', $siswaSchoolId))
            ->first();

        if (!$jadwal) {
            return [
                'ok' => false,
                'status' => 'warning',
                'sound' => 'warning',
                'type' => 'hari_libur',
                'message' => 'Tidak ada jadwal sekolah aktif hari ini (Hari Libur).',
                'data' => [
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis ?? '-',
                    'foto' => $siswa->foto_url ?? null,
                    'role' => 'Siswa',
                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                    'status' => 'Hari Libur',
                    'jam' => $now->format('H:i:s'),
                ],
                'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                'attendance' => null,
            ];
        }

        $jamMasuk = !empty($jadwal->jam_masuk) ? Carbon::parse($today . ' ' . $jadwal->jam_masuk, $schoolTz) : null;
        $jamPulang = !empty($jadwal->jam_pulang) ? Carbon::parse($today . ' ' . $jadwal->jam_pulang, $schoolTz) : null;
        $awalAbsenMasuk = !empty($jadwal->awal_absen_masuk) ? Carbon::parse($today . ' ' . $jadwal->awal_absen_masuk, $schoolTz) : null;
        $akhirAbsenMasuk = !empty($jadwal->akhir_absen_masuk) ? Carbon::parse($today . ' ' . $jadwal->akhir_absen_masuk, $schoolTz) : null;
        $akhirAbsenPulang = !empty($jadwal->akhir_absen_pulang) ? Carbon::parse($today . ' ' . $jadwal->akhir_absen_pulang, $schoolTz) : null;
        $batasTelat = $jamMasuk;

        // Cek Sesi Izin Gerbang Guru
        $schoolGateCardUids = GateCard::where('school_id', $siswaSchoolId)
            ->get()
            ->map(fn($g) => $g->uid_rfid ?: ("gate_" . $g->id))
            ->filter()
            ->toArray();
        $teacherSession = TeacherCheckoutSession::where('expires_at', '>', $now)
            ->where('status', 'open')
            ->where(function ($q) use ($siswaSchoolId, $schoolGateCardUids) {
                $q->whereIn('uid_rfid', $schoolGateCardUids)
                  ->orWhereHas('teacher', fn($t) => $t->where('school_id', $siswaSchoolId));
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $checkoutEnabled = Setting::where('school_id', $siswaSchoolId)
            ->where('setting_key', 'enable_checkout_attendance')
            ->value('setting_value') ?? 'true';

        try {
            DB::beginTransaction();

            $att = Attendance::where('student_id', $siswa->id)
                ->where('tanggal', $today)
                ->lockForUpdate()
                ->first();

            // Override auto-alpha jika siswa hadir langsung
            $isSystemAlpha = $att && $att->jam_masuk === null && ($att->is_auto_alpha || in_array($att->status, ['S', 'I', 'A', 'B']));
            if ($isSystemAlpha) {
                $att->delete();
                $att = null;
            }

            // A. Cek status lengkap (sudah masuk dan sudah pulang)
            if ($att && $att->jam_pulang) {
                $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, "Scan Kegiatan ({$channelLabel})");
                if (!empty($recordedKegiatans)) {
                    DB::commit();
                    $namaKeg = implode(', ', $recordedKegiatans);
                    return [
                        'ok' => true,
                        'status' => 'success',
                        'sound' => 'ok',
                        'type' => 'absen_kegiatan',
                        'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Kegiatan: ' . $namaKeg,
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                            'jam_pulang' => Carbon::parse($att->jam_pulang)->format('H:i'),
                            'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                        'attendance' => $att,
                    ];
                }

                DB::rollBack();
                return [
                    'ok' => true,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'type' => 'sudah_lengkap',
                    'message' => "Siswa {$siswa->nama} sudah lengkap absen Masuk & Pulang hari ini.",
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Lengkap',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                        'jam_pulang' => Carbon::parse($att->jam_pulang)->format('H:i'),
                        'keterangan' => 'Sudah lengkap Masuk & Pulang',
                        'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                    'attendance' => $att,
                ];
            }

            // Tentukan aksi: Masuk atau Pulang
            $isPulangAction = false;
            if ($reqMode === 'pulang') {
                $isPulangAction = true;
            } elseif ($reqMode === 'masuk') {
                $isPulangAction = false;
            } else {
                // Auto mode (jangan langsung pulang jika baru saja absen masuk)
                $isPulangAction = ($att && $att->jam_masuk !== null)
                    && !$this->isWithinCheckoutGap($att->tanggal, $att->jam_masuk, $now);
            }

            // B. EKSEKUSI PULANG
            if ($isPulangAction) {
                if (!$att || !$att->jam_masuk) {
                    DB::rollBack();
                    return [
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'belum_absen_masuk',
                        'message' => "Siswa {$siswa->nama} belum melakukan absen masuk hari ini.",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Belum Masuk',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => '--:--',
                            'jam_pulang' => '--:--',
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                        'attendance' => null,
                    ];
                }

                if ($checkoutEnabled === 'false' && $channel !== 'mobile' && empty($options['ignore_checkout_toggle'])) {
                    $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, "Auto Pulang ({$channelLabel})");
                    DB::rollBack();
                    return [
                        'ok' => true,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'sudah_absen_masuk',
                        'message' => "Siswa {$siswa->nama} sudah absen masuk (absen pulang dinonaktifkan).",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Sudah Masuk',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                            'jam_pulang' => '--:--',
                            'keterangan' => 'Absen pulang dinonaktifkan',
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                        'attendance' => $att,
                    ];
                }

                // Cek batas akhir absen pulang
                if ($akhirAbsenPulang && $now->gt($akhirAbsenPulang) && !$teacherSession) {
                    $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, "Auto Pulang ({$channelLabel})");
                    if (!empty($recordedKegiatans)) {
                        DB::commit();
                        $namaKeg = implode(', ', $recordedKegiatans);
                        return [
                            'ok' => true,
                            'status' => 'success',
                            'sound' => 'ok',
                            'type' => 'absen_kegiatan',
                            'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Kegiatan: ' . $namaKeg,
                                'jam' => $now->format('H:i:s'),
                                'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                                'uid' => $uid,
                            ],
                            'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                            'attendance' => $att,
                        ];
                    }

                    DB::rollBack();
                    $akhirPulangStr = $akhirAbsenPulang->format('H:i');
                    return [
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'checkout_closed',
                        'message' => "Waktu absen pulang telah ditutup (ditutup pukul {$akhirPulangStr}).",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Pulang Ditutup',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                            'jam_pulang' => '--:--',
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                        'attendance' => $att,
                    ];
                }

                // Cek awal absen pulang
                if ($jamPulang && $now->lt($jamPulang) && !$teacherSession) {
                    $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, "Auto Pulang ({$channelLabel})");
                    if (!empty($recordedKegiatans)) {
                        DB::commit();
                        $namaKeg = implode(', ', $recordedKegiatans);
                        return [
                            'ok' => true,
                            'status' => 'success',
                            'sound' => 'ok',
                            'type' => 'absen_kegiatan',
                            'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Kegiatan: ' . $namaKeg,
                                'jam' => $now->format('H:i:s'),
                                'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                                'uid' => $uid,
                            ],
                            'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                            'attendance' => $att,
                        ];
                    }

                    DB::rollBack();
                    $jamPulangStr = $jamPulang->format('H:i');
                    $isStillInCheckinWindow = ($reqMode === 'auto') && ($awalAbsenMasuk && $now->between($awalAbsenMasuk, $akhirAbsenMasuk ?? $jamPulang));
                    if ($isStillInCheckinWindow) {
                        return [
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'sudah_absen_masuk',
                            'message' => "Siswa {$siswa->nama} sudah absen masuk (Waktu pulang belum dimulai, dibuka pukul {$jamPulangStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                                'jam_pulang' => '--:--',
                                'keterangan' => "Waktu pulang dibuka pukul {$jamPulangStr}",
                                'uid' => $uid,
                            ],
                            'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                            'attendance' => $att,
                        ];
                    } else {
                        return [
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'no_authorization',
                            'message' => "Absen Pulang belum dibuka (dibuka pukul {$jamPulangStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Belum Jam Pulang',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                                'jam_pulang' => '--:--',
                                'keterangan' => "Waktu pulang dibuka pukul {$jamPulangStr}",
                                'uid' => $uid,
                            ],
                            'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                            'attendance' => $att,
                        ];
                    }
                }

                $masuk = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk, $schoolTz);
                $totalSeconds = (int) round(abs($masuk->diffInSeconds($now, false)));
                $newStatus = $att->status;
                $newKeterangan = $att->keterangan;

                if ($att->status === 'B') {
                    $newStatus = ($batasTelat && $masuk->gt($batasTelat)) ? 'T' : 'H';
                    if ($newKeterangan) {
                        $newKeterangan = trim(str_replace('[Auto: Tidak Absen Pulang]', '', $newKeterangan));
                        if (empty($newKeterangan)) $newKeterangan = null;
                    }
                }

                $updateData = [
                    'jam_pulang' => $now->toTimeString(),
                    'total_seconds' => $totalSeconds,
                    'status' => $newStatus,
                    'keterangan' => $newKeterangan,
                    'updated_at' => now(),
                ];
                if (in_array($channel, ['web_face', 'mobile'])) {
                    if ($similarity !== null) {
                        $updateData['is_face_matched'] = true;
                        $updateData['face_similarity_score'] = $similarity;
                    }
                }
                if ($snapshotPath) {
                    $updateData['photo_checkin_path'] = $snapshotPath;
                }

                $att->update($updateData);

                $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, "Auto Pulang ({$channelLabel})");
                DB::commit();

                $hours = floor($totalSeconds / 3600);
                $mins = floor(($totalSeconds % 3600) / 60);

                // Notifikasi WhatsApp Pulang
                try {
                    $this->wa->sendCheckOut($siswa->nama, $siswa->no_wa, $now->format('H:i'), $hours, $mins, $channelLabel, $siswaSchoolId, $masuk->format('H:i'), $siswa->wa_ortu, $now->format('d/m/Y'));
                } catch (\Throwable $e) {
                    Log::error("WA Student Checkout Error ({$channel}): " . $e->getMessage());
                }

                // Notifikasi Push FCM
                try {
                    FcmNotificationService::sendToSiswa($siswa, "Presensi Pulang Tercatat", "Halo {$siswa->nama}, absensi pulang Anda via {$channelLabel} berhasil dicatat pukul {$now->format('H:i')}.", [
                        'type' => 'checkout_siswa',
                        'jam' => $now->format('H:i'),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("FCM Student Checkout Error ({$channel}): " . $e->getMessage());
                }

                return [
                    'ok' => true,
                    'status' => 'success',
                    'sound' => 'ok',
                    'type' => 'absen_pulang',
                    'message' => "Absen Pulang Berhasil: {$siswa->nama}",
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Pulang',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => $masuk->format('H:i'),
                        'jam_pulang' => $now->format('H:i'),
                        'durasi' => "{$hours}j {$mins}m",
                        'keterangan' => 'Pulang',
                        'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                    'attendance' => $att,
                ];
            }

            // C. EKSEKUSI MASUK
            if ($att && $att->jam_masuk) {
                DB::rollBack();
                return [
                    'ok' => true,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'type' => 'sudah_absen_masuk',
                    'message' => "Siswa {$siswa->nama} sudah absen masuk pukul " . Carbon::parse($att->jam_masuk)->format('H:i'),
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Sudah Masuk',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                        'jam_pulang' => '--:--',
                        'keterangan' => 'Sudah absen masuk',
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                    'attendance' => $att,
                ];
            }

            // Cek Awal Masuk & Akhir Masuk
            $isBeforeAwalMasuk = $awalAbsenMasuk && $now->lt($awalAbsenMasuk);
            $isAfterAkhirMasuk = $akhirAbsenMasuk && $now->gt($akhirAbsenMasuk);

            if ($isBeforeAwalMasuk || $isAfterAkhirMasuk) {
                $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, "Scan Kegiatan ({$channelLabel})");
                if (!empty($recordedKegiatans)) {
                    DB::commit();
                    $namaKeg = implode(', ', $recordedKegiatans);
                    return [
                        'ok' => true,
                        'status' => 'success',
                        'sound' => 'ok',
                        'type' => 'absen_kegiatan',
                        'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Kegiatan: ' . $namaKeg,
                            'jam' => $now->format('H:i:s'),
                            'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                        'attendance' => $att,
                    ];
                }

                if ($isBeforeAwalMasuk) {
                    DB::rollBack();
                    $awalMasukStr = $awalAbsenMasuk->format('H:i');
                    return [
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'too_early',
                        'message' => "Waktu absen masuk belum dibuka (dibuka pukul {$awalMasukStr}).",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Belum Buka',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                        'attendance' => null,
                    ];
                }

                if ($isAfterAkhirMasuk) {
                    DB::rollBack();
                    $akhirMasukStr = $akhirAbsenMasuk->format('H:i');
                    return [
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'checkin_closed',
                        'message' => "Waktu absen masuk telah ditutup (ditutup pukul {$akhirMasukStr}).",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Masuk Ditutup',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                        'attendance' => null,
                    ];
                }
            }

            $status = 'H';
            $keterangan = null;
            $statusLabel = 'Hadir Tepat Waktu';

            if ($batasTelat && $now->gt($batasTelat)) {
                $status = 'T';
                $diff = $now->timestamp - $batasTelat->timestamp;
                $jam = floor($diff / 3600);
                $menit = floor(($diff % 3600) / 60);
                $keterangan = $jam > 0 ? "Telat {$jam} jam {$menit} menit" : "Telat {$menit} menit";
                $statusLabel = "Terlambat ({$keterangan})";
            }

            $attData = [
                'student_id' => $siswa->id,
                'tanggal' => $today,
                'jam_masuk' => $now->toTimeString(),
                'status' => $status,
                'keterangan' => $keterangan,
                'updated_at' => now(),
            ];
            if (in_array($channel, ['web_face', 'mobile'])) {
                if ($similarity !== null) {
                    $attData['is_face_matched'] = true;
                    $attData['face_similarity_score'] = $similarity;
                }
            }
            if ($snapshotPath) {
                $attData['photo_checkin_path'] = $snapshotPath;
            }

            if ($att) {
                $att->update($attData);
            } else {
                $attData['created_at'] = now();
                $att = Attendance::create($attData);
            }

            $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, "Auto Masuk ({$channelLabel})");
            DB::commit();

            // Notifikasi WhatsApp Masuk
            try {
                $this->wa->sendCheckIn($siswa->nama, $siswa->no_wa, $now->format('H:i'), $status, $siswaSchoolId, $keterangan, $siswa->wa_ortu, $siswa->kelas->nama_kelas ?? '-');
            } catch (\Throwable $e) {
                Log::error("WA Student Checkin Error ({$channel}): " . $e->getMessage());
            }

            // Notifikasi Push FCM
            try {
                $readableStatus = $status === 'T' ? 'Terlambat' : 'Hadir';
                FcmNotificationService::sendToSiswa($siswa, "Presensi Masuk Tercatat", "Halo {$siswa->nama}, absensi masuk Anda via {$channelLabel} berhasil dicatat pukul {$now->format('H:i')} (Status: {$readableStatus}).", [
                    'type' => 'checkin_siswa',
                    'status' => $readableStatus,
                    'jam' => $now->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                Log::error("FCM Student Checkin Error ({$channel}): " . $e->getMessage());
            }

            return [
                'ok' => true,
                'status' => 'success',
                'sound' => ($status === 'T' ? 'warning' : 'ok'),
                'type' => 'absen_masuk',
                'message' => "Absen Masuk Berhasil: {$siswa->nama} ({$statusLabel})",
                'data' => [
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis ?? '-',
                    'foto' => $siswa->foto_url ?? null,
                    'role' => 'Siswa',
                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                    'status' => $status === 'T' ? 'Terlambat' : 'Hadir',
                    'jam' => $now->format('H:i:s'),
                    'jam_masuk' => $now->format('H:i'),
                    'jam_pulang' => '--:--',
                    'keterangan' => $keterangan ?: 'Tepat Waktu',
                    'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                    'uid' => $uid,
                ],
                'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                'attendance' => $att,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Student Attendance Core Error ({$channel}): " . $e->getMessage());
            return [
                'ok' => false,
                'status' => 'error',
                'sound' => 'error',
                'type' => 'system_error',
                'message' => 'Terjadi kesalahan sistem saat memproses absensi siswa: ' . $e->getMessage(),
                'data' => null,
                'person' => ['type' => 'siswa', 'id' => $siswa->id, 'key' => 'siswa:' . $siswa->id],
                'attendance' => null,
            ];
        }
    }

    /**
     * =========================================================================
     * INTI LOGIKA PRESENSI GURU / PEGAWAI (MASUK / PULANG / SHIFT)
     * =========================================================================
     */
    public function recordTeacherAttendance(Guru $guru, array $options = []): array
    {
        $schoolId = $options['school_id'] ?? $guru->school_id;
        $schoolTz = $this->getSchoolTimezone($schoolId);
        $now = isset($options['now']) && $options['now'] instanceof Carbon
            ? $options['now']->copy()->setTimezone($schoolTz)
            : Carbon::now($schoolTz);
        $today = $now->format('Y-m-d');

        $reqMode = $options['mode'] ?? 'auto';
        $channel = $options['channel'] ?? 'web_face';
        $similarity = isset($options['similarity']) ? (float) $options['similarity'] : null;
        $snapshotPath = $options['snapshot_path'] ?? null;
        $channelLabel = $this->getChannelLabel($channel);
        $uid = $options['uid'] ?? ($guru->uid_rfid ?? null);
        $guruSchoolId = $guru->school_id ?: $schoolId;

        try {
            DB::beginTransaction();

            $shift = $guru->getShiftForDate($now);
            if (!$shift) {
                DB::rollBack();
                return [
                    'ok' => false,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'type' => 'no_shift',
                    'message' => 'Guru tidak memiliki jadwal shift aktif hari ini.',
                    'data' => [
                        'nama' => $guru->nama,
                        'nis' => $guru->nip ?? '-',
                        'foto' => $guru->foto_url ?? null,
                        'role' => 'Guru / Staf',
                        'kelas' => $guru->jabatan ?? 'Dewan Guru',
                        'status' => 'Tanpa Shift',
                        'jam' => $now->format('H:i:s'),
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                    'absensi_guru' => null,
                ];
            }

            $absensiGuru = AbsensiGuru::where('guru_id', $guru->id)
                ->where('tanggal', $today)
                ->where('school_id', $guruSchoolId)
                ->whereNull('jadwal_pelajaran_id')
                ->lockForUpdate()
                ->first();

            // Cek jika sudah lengkap masuk dan pulang
            if ($absensiGuru && $absensiGuru->jam_pulang) {
                DB::rollBack();
                return [
                    'ok' => true,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'type' => 'sudah_lengkap',
                    'message' => "Guru {$guru->nama} sudah lengkap absen Masuk & Pulang hari ini.",
                    'data' => [
                        'nama' => $guru->nama,
                        'nis' => $guru->nip ?? '-',
                        'foto' => $guru->foto_url ?? null,
                        'role' => 'Guru / Staf',
                        'kelas' => $shift->nama_shift,
                        'status' => 'Lengkap',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                        'jam_pulang' => Carbon::parse($absensiGuru->jam_pulang)->format('H:i'),
                        'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                    'absensi_guru' => $absensiGuru,
                ];
            }

            $isPulang = false;
            if ($reqMode === 'pulang') {
                $isPulang = true;
            } elseif ($reqMode === 'masuk') {
                $isPulang = false;
            } else {
                // Auto mode (jangan langsung pulang jika baru saja absen masuk)
                $isPulang = ($absensiGuru && $absensiGuru->jam_masuk !== null)
                    && !$this->isWithinCheckoutGap($absensiGuru->tanggal, $absensiGuru->jam_masuk, $now);
            }

            // A. EKSEKUSI PULANG GURU
            if ($isPulang) {
                if (!$absensiGuru || !$absensiGuru->jam_masuk) {
                    DB::rollBack();
                    return [
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'belum_absen_masuk',
                        'message' => "Guru {$guru->nama} belum melakukan absen masuk hari ini.",
                        'data' => [
                            'nama' => $guru->nama,
                            'nis' => $guru->nip ?? '-',
                            'foto' => $guru->foto_url ?? null,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => 'Belum Masuk',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                        'absensi_guru' => null,
                    ];
                }

                $checkoutEnabled = Setting::where('school_id', $guruSchoolId)
                    ->where('setting_key', 'enable_checkout_teacher')
                    ->value('setting_value') ?? 'false';

                if ($checkoutEnabled === 'false' && $channel !== 'mobile' && empty($options['ignore_checkout_toggle'])) {
                    DB::commit();
                    return [
                        'ok' => true,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'absen_sudah_masuk_guru',
                        'message' => "Guru {$guru->nama} sudah absen masuk sebelumnya (absen pulang guru dinonaktifkan).",
                        'data' => [
                            'nama' => $guru->nama,
                            'nis' => $guru->nip ?? '-',
                            'foto' => $guru->foto_url ?? null,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => 'Sudah Masuk',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                        'absensi_guru' => $absensiGuru,
                    ];
                }

                $inCheckoutWindow = $shift->isInCheckOutWindow($now->format('H:i:s'));
                $schoolGateCardUids = GateCard::where('school_id', $guruSchoolId)
                    ->get()
                    ->map(fn($g) => $g->uid_rfid ?: ("gate_" . $g->id))
                    ->filter()
                    ->toArray();
                $gateSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                    ->where('status', 'open')
                    ->where(function ($q) use ($guruSchoolId, $schoolGateCardUids) {
                        $q->whereIn('uid_rfid', $schoolGateCardUids)
                          ->orWhereHas('teacher', fn($t) => $t->where('school_id', $guruSchoolId));
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$inCheckoutWindow && !$gateSession) {
                    if ($reqMode === 'auto' && $shift->isInCheckInWindow($now->format('H:i:s'))) {
                        DB::commit();
                        return [
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'absen_sudah_masuk_guru',
                            'message' => "Guru {$guru->nama} sudah absen masuk (Waktu pulang belum dimulai).",
                            'data' => [
                                'nama' => $guru->nama,
                                'nis' => $guru->nip ?? '-',
                                'foto' => $guru->foto_url ?? null,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ],
                            'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                            'absensi_guru' => $absensiGuru,
                        ];
                    }

                    DB::rollBack();
                    $timeNow = $now->format('H:i:s');
                    if ($shift->awal_absen_pulang && $timeNow < Carbon::parse($shift->awal_absen_pulang)->format('H:i:s')) {
                        $awalPulangStr = Carbon::parse($shift->awal_absen_pulang)->format('H:i');
                        $msg = "Absen pulang belum dibuka (dibuka pukul {$awalPulangStr}) .";
                    } elseif ($shift->akhir_absen_pulang && $timeNow > Carbon::parse($shift->akhir_absen_pulang)->format('H:i:s')) {
                        $akhirPulangStr = Carbon::parse($shift->akhir_absen_pulang)->format('H:i');
                        $msg = "Waktu absen pulang guru telah ditutup (ditutup pukul {$akhirPulangStr}).";
                    } else {
                        $windowStr = ($shift->awal_absen_pulang && $shift->akhir_absen_pulang)
                            ? Carbon::parse($shift->awal_absen_pulang)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_pulang)->format('H:i')
                            : '';
                        $msg = "Di luar jam absen pulang guru ({$windowStr})";
                    }

                    return [
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'outside_checkout_window',
                        'message' => $msg,
                        'data' => [
                            'nama' => $guru->nama,
                            'nis' => $guru->nip ?? '-',
                            'foto' => $guru->foto_url ?? null,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => 'Di Luar Jam Pulang',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ],
                        'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                        'absensi_guru' => null,
                    ];
                }

                $masuk = Carbon::parse($absensiGuru->tanggal . ' ' . $absensiGuru->jam_masuk, $schoolTz);
                $totalSeconds = (int) round(abs($masuk->diffInSeconds($now, false)));

                $updateData = [
                    'jam_pulang' => $now->toTimeString(),
                    'updated_at' => now(),
                ];
                if (in_array($channel, ['web_face', 'mobile'])) {
                    if ($similarity !== null) {
                        $updateData['is_face_matched'] = true;
                        $updateData['face_similarity_score'] = $similarity;
                    }
                }
                if ($snapshotPath) {
                    $updateData['photo_checkin_path'] = $snapshotPath;
                }

                $absensiGuru->update($updateData);
                DB::commit();

                $hours = floor($totalSeconds / 3600);
                $mins = floor(($totalSeconds % 3600) / 60);
                $authorizedBy = $gateSession ? $gateSession->teacher_name : $channelLabel;

                try {
                    $this->wa->sendCheckOut($guru->nama, $guru->no_wa, $now->format('H:i'), $hours, $mins, $authorizedBy, $guruSchoolId, $masuk->format('H:i'), null, $now->format('d/m/Y'));
                } catch (\Throwable $e) {
                    Log::error("WA Guru Checkout Error ({$channel}): " . $e->getMessage());
                }

                return [
                    'ok' => true,
                    'status' => 'success',
                    'sound' => 'ok',
                    'type' => 'absen_pulang_guru',
                    'message' => "Absen Pulang Berhasil: {$guru->nama}",
                    'data' => [
                        'nama' => $guru->nama,
                        'nis' => $guru->nip ?? '-',
                        'foto' => $guru->foto_url ?? null,
                        'role' => 'Guru / Staf',
                        'kelas' => $shift->nama_shift,
                        'status' => 'Pulang',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => $masuk->format('H:i'),
                        'jam_pulang' => $now->format('H:i'),
                        'keterangan' => "Total durasi: {$hours}j {$mins}m",
                        'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                        'authorized_by' => $authorizedBy,
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                    'absensi_guru' => $absensiGuru,
                ];
            }

            // B. EKSEKUSI MASUK GURU
            if ($absensiGuru && $absensiGuru->jam_masuk) {
                DB::rollBack();
                return [
                    'ok' => true,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'type' => 'absen_sudah_masuk_guru',
                    'message' => "Guru {$guru->nama} sudah absen masuk pukul " . Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                    'data' => [
                        'nama' => $guru->nama,
                        'nis' => $guru->nip ?? '-',
                        'foto' => $guru->foto_url ?? null,
                        'role' => 'Guru / Staf',
                        'kelas' => $shift->nama_shift,
                        'status' => 'Sudah Masuk',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                        'jam_pulang' => '--:--',
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                    'absensi_guru' => $absensiGuru,
                ];
            }

            if (!$shift->isInCheckInWindow($now->format('H:i:s'))) {
                DB::rollBack();
                $timeNow = $now->format('H:i:s');
                if ($shift->awal_absen_masuk && $timeNow < Carbon::parse($shift->awal_absen_masuk)->format('H:i:s')) {
                    $awalMasukStr = Carbon::parse($shift->awal_absen_masuk)->format('H:i');
                    $msg = "Waktu absen masuk guru belum dibuka (dibuka pukul {$awalMasukStr}).";
                } elseif ($shift->akhir_absen_masuk && $timeNow > Carbon::parse($shift->akhir_absen_masuk)->format('H:i:s')) {
                    $akhirMasukStr = Carbon::parse($shift->akhir_absen_masuk)->format('H:i');
                    $msg = "Waktu absen masuk guru telah ditutup (ditutup pukul {$akhirMasukStr}).";
                } else {
                    $windowStr = ($shift->awal_absen_masuk && $shift->akhir_absen_masuk)
                        ? Carbon::parse($shift->awal_absen_masuk)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_masuk)->format('H:i')
                        : '';
                    $msg = "Di luar jam absen masuk guru ({$windowStr})";
                }

                return [
                    'ok' => false,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'type' => 'outside_checkin_window',
                    'message' => $msg,
                    'data' => [
                        'nama' => $guru->nama,
                        'nis' => $guru->nip ?? '-',
                        'foto' => $guru->foto_url ?? null,
                        'role' => 'Guru / Staf',
                        'kelas' => $shift->nama_shift,
                        'status' => 'Di Luar Jam',
                        'jam' => $now->format('H:i:s'),
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                    'absensi_guru' => null,
                ];
            }

            $status = 'Hadir';
            $statusKehadiran = 'tepat_waktu';
            $menitTerlambat = 0;
            $keterangan = null;

            if ($shift->isLate($now->format('H:i:s'))) {
                $status = 'Terlambat';
                $statusKehadiran = 'terlambat';
                $menitTerlambat = $shift->calculateLateMinutes($now->format('H:i:s'));
                $keterangan = "Terlambat {$menitTerlambat} m ({$shift->nama_shift})";
            } else {
                $status = 'Hadir';
                $statusKehadiran = 'tepat_waktu';
                $keterangan = "Tepat Waktu ({$shift->nama_shift})";
            }

            $guruData = [
                'guru_id' => $guru->id,
                'school_id' => $guruSchoolId,
                'jadwal_pelajaran_id' => null,
                'shift_id' => $shift->id,
                'tanggal' => $today,
                'jam_masuk' => $now->toTimeString(),
                'waktu_hadir' => $now,
                'menit_terlambat' => $menitTerlambat,
                'status' => $status,
                'status_kehadiran' => $statusKehadiran,
                'keterangan' => $keterangan,
                'updated_at' => now(),
            ];
            if (in_array($channel, ['web_face', 'mobile'])) {
                if ($similarity !== null) {
                    $guruData['is_face_matched'] = true;
                    $guruData['face_similarity_score'] = $similarity;
                }
            }
            if ($snapshotPath) {
                $guruData['photo_checkin_path'] = $snapshotPath;
            }

            if ($absensiGuru) {
                $absensiGuru->update($guruData);
            } else {
                $guruData['created_at'] = now();
                $absensiGuru = AbsensiGuru::create($guruData);
            }

            DB::commit();

            try {
                $this->wa->sendCheckIn($guru->nama, $guru->no_wa, $now->format('H:i'), $status, $guruSchoolId, $keterangan, null, '-');
            } catch (\Throwable $e) {
                Log::error("WA Guru Checkin Error ({$channel}): " . $e->getMessage());
            }

            $respMsg = $status === 'Terlambat'
                ? "Masuk ({$status}): {$guru->nama} (+{$menitTerlambat}m)"
                : "Selamat Datang, {$guru->nama}.";

            return [
                'ok' => true,
                'status' => 'success',
                'sound' => ($status === 'Terlambat' ? 'warning' : 'ok'),
                'type' => 'absen_masuk_guru',
                'message' => $respMsg,
                'data' => [
                    'nama' => $guru->nama,
                    'nis' => $guru->nip ?? '-',
                    'foto' => $guru->foto_url ?? null,
                    'role' => 'Guru / Staf',
                    'kelas' => $shift->nama_shift,
                    'shift' => $shift,
                    'shift_name' => $shift->nama_shift,
                    'status' => $status,
                    'jam' => $now->format('H:i:s'),
                    'jam_masuk' => $now->format('H:i'),
                    'jam_pulang' => '--:--',
                    'keterangan' => $keterangan,
                    'similarity' => $similarity ? (round($similarity * 100) . '%') : null,
                    'uid' => $uid,
                ],
                'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                'absensi_guru' => $absensiGuru,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Teacher Attendance Core Error ({$channel}): " . $e->getMessage());
            return [
                'ok' => false,
                'status' => 'error',
                'sound' => 'error',
                'type' => 'system_error',
                'message' => 'Gagal memproses absensi guru: ' . $e->getMessage(),
                'data' => null,
                'person' => ['type' => 'guru', 'id' => $guru->id, 'key' => 'guru:' . $guru->id],
                'absensi_guru' => null,
            ];
        }
    }

    /**
     * =========================================================================
     * INTI LOGIKA PEMBUKA / PENUTUP GERBANG (GATE CARD SCAN)
     * =========================================================================
     */
    public function handleGateCardScan(GateCard $gateCard, array $options = []): array
    {
        $schoolId = $options['school_id'] ?? $gateCard->school_id;
        $schoolTz = $this->getSchoolTimezone($schoolId);
        $now = isset($options['now']) && $options['now'] instanceof Carbon
            ? $options['now']->copy()->setTimezone($schoolTz)
            : Carbon::now($schoolTz);

        $uid = $options['uid'] ?? ($gateCard->uid_rfid ?? null);
        $gateName = $gateCard->name ?: 'Kartu Gerbang';

        try {
            DB::beginTransaction();

            $sessionUid = $gateCard->uid_rfid ?: ("gate_" . $gateCard->id);

            $existingSession = TeacherCheckoutSession::where('uid_rfid', $sessionUid)
                ->where('status', 'open')
                ->where('expires_at', '>', $now)
                ->first();

            if ($existingSession) {
                $existingSession->update(['status' => 'closed']);
                DB::commit();

                return [
                    'ok' => true,
                    'status' => 'success',
                    'sound' => 'ok',
                    'type' => 'gate_closed',
                    'message' => 'Absen pulang ditutup (' . $gateName . ').',
                    'data' => [
                        'nama' => $gateName,
                        'role' => 'Kartu Gerbang',
                        'status' => 'Gerbang Ditutup',
                        'jam' => $now->format('H:i:s'),
                        'uid' => $uid,
                    ],
                    'person' => ['type' => 'gate', 'id' => $gateCard->id, 'key' => 'gate:' . $gateCard->id],
                ];
            }

            TeacherCheckoutSession::create([
                'teacher_id' => $gateCard->guru_id,
                'uid_rfid' => $sessionUid,
                'teacher_name' => $gateName,
                'created_at' => $now,
                'expires_at' => $now->copy()->addMinutes(30),
                'status' => 'open',
            ]);

            DB::commit();

            return [
                'ok' => true,
                'status' => 'success',
                'sound' => 'ok',
                'type' => 'gate_opened',
                'message' => 'Absen pulang dibuka selama 30 menit (' . $gateName . ').',
                'data' => [
                    'nama' => $gateName,
                    'role' => 'Kartu Gerbang',
                    'status' => 'Gerbang Dibuka (30 Menit)',
                    'jam' => $now->format('H:i:s'),
                    'uid' => $uid,
                ],
                'person' => ['type' => 'gate', 'id' => $gateCard->id, 'key' => 'gate:' . $gateCard->id],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Gate Card Scan Error: " . $e->getMessage());
            return [
                'ok' => false,
                'status' => 'error',
                'sound' => 'error',
                'type' => 'system_error',
                'message' => 'Gagal memproses sesi gerbang: ' . $e->getMessage(),
                'data' => null,
                'person' => ['type' => 'gate', 'id' => $gateCard->id, 'key' => 'gate:' . $gateCard->id],
            ];
        }
    }

    /**
     * Helper auto-record attendance untuk kegiatan aktif yang sedang berjalan
     */
    public function recordActiveKegiatans($schoolId, Siswa $siswa, Carbon $now, string $today, string $keterangan = 'Auto dari Scan Mesin'): array
    {
        $activeKegiatans = Kegiatan::where('school_id', $schoolId)
            ->where('is_active', 1)
            ->get()
            ->filter(function ($keg) use ($now) {
                return $keg->isScheduledNow($now);
            });

        $recordedKegiatans = [];
        foreach ($activeKegiatans as $keg) {
            if (!$keg->isStudentEligible($siswa)) {
                continue;
            }

            $alreadyKeg = KegiatanAttendance::where('kegiatan_id', $keg->id)
                ->where('student_id', $siswa->id)
                ->where('tanggal', $today)
                ->exists();

            if (!$alreadyKeg) {
                KegiatanAttendance::create([
                    'school_id'   => $schoolId,
                    'kegiatan_id' => $keg->id,
                    'student_id'  => $siswa->id,
                    'tanggal'     => $today,
                    'jam_masuk'   => $now->toTimeString(),
                    'status'      => 'H',
                    'keterangan'  => $keterangan,
                ]);
                $recordedKegiatans[] = $keg->nama_kegiatan;

                try {
                    $telegramService = app(TelegramService::class);
                    $telegramService->sendKegiatanCheckIn(
                        namaSiswa: $siswa->nama,
                        namaKegiatan: $keg->nama_kegiatan,
                        jam: $now->format('H:i'),
                        tanggal: $now->translatedFormat('l, d F Y'),
                        schoolId: $schoolId,
                        chatIdSiswa: $siswa->telegram_chat_id ?: null,
                        chatIdOrtu: $siswa->telegram_ortu_chat_id ?: null
                    );
                } catch (\Throwable $e) {
                    Log::error("Failed to send Telegram for kegiatan attendance: " . $e->getMessage());
                }
            }
        }
        return $recordedKegiatans;
    }
}

