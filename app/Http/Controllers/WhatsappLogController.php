<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MessageQueue;
use App\Models\Attendance;
use App\Models\AbsensiGuru;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Setting;
use Carbon\Carbon;

class WhatsappLogController extends Controller
{
    public function index(Request $request, \App\Services\NotificationDeliveryService $deliveryService)
    {
        $query = MessageQueue::orderBy('created_at', 'desc');
        $schoolId = null;

        if (!auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $deliveryStats = $deliveryService->getTodayDeliveryStats($schoolId);

        $logs = $query->paginate(20)->withQueryString();
        return view('whatsapp.logs', compact('logs', 'deliveryStats'));
    }

    /**
     * Halaman Detail Delivery Notifikasi vs Absen & Status Last Seen Seluruh Kontak
     */
    public function deliveryDetail(Request $request, \App\Services\NotificationDeliveryService $deliveryService)
    {
        $user = auth()->user();
        $schoolId = ($user && !$user->isSuperAdmin()) ? $user->school_id : ($request->school_id ?: null);

        $activeTab = $request->get('tab', 'delivery'); // 'delivery' or 'contacts'
        $date = $request->get('date', now()->format('Y-m-d'));
        $search = trim($request->get('search', ''));
        $filterStatus = $request->get('status', 'all'); // 'all', 'queued', 'skipped', 'no_phone'
        $filterRole = $request->get('role', 'all'); // 'all', 'siswa', 'guru', 'ortu'
        $filterKelas = $request->get('kelas_id', 'all');

        $expiryDays = max(1, (int) Setting::getVal('last_seen_expiry_days', 3, $schoolId));
        $expiryHours = $expiryDays * 24;

        // Kelases untuk filter dropdown
        $kelases = $schoolId ? Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get() : Kelas::orderBy('nama_kelas')->get();

        $summaryStats = $this->getContactsSummaryStats($schoolId, $expiryHours);

        if ($activeTab === 'contacts') {
            // === TAB 2: MONITORING DATABASE STATUS LAST SEEN SEMUA KONTAK ===
            $contactsList = $this->getContactsLastSeenList($schoolId, $expiryHours, $filterRole, $filterStatus, $filterKelas, $search);

            return view('whatsapp.delivery_detail', compact(
                'activeTab', 'date', 'search', 'filterStatus', 'filterRole', 'filterKelas',
                'expiryDays', 'expiryHours', 'kelases', 'contactsList', 'summaryStats', 'schoolId'
            ));
        }

        // === TAB 1: DETAIL DELIVERY NOTIFIKASI VS ABSEN PADA TANGGAL TERPILIH ===
        $allDailyRows = $this->getDailyAttendanceDeliveryRows($schoolId, $date, $expiryHours, $filterRole, $search);
        $dailySummary = $this->calculateDailyDeliverySummary($allDailyRows);

        if ($filterStatus !== 'all') {
            $deliveryRows = array_values(array_filter($allDailyRows, function ($row) use ($filterStatus) {
                return $this->matchFilterStatus($row, $filterStatus);
            }));
        } else {
            $deliveryRows = $allDailyRows;
        }

        return view('whatsapp.delivery_detail', compact(
            'activeTab', 'date', 'search', 'filterStatus', 'filterRole', 'filterKelas',
            'expiryDays', 'expiryHours', 'kelases', 'deliveryRows', 'dailySummary', 'summaryStats', 'schoolId'
        ));
    }

    /**
     * Mengirim pesan reminder aktivasi last seen manual ke satu kontak
     */
    public function sendSingleReminder(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'name'  => 'required|string',
        ]);

        $phone = $this->normalizePhone($request->phone);
        if (!$phone) {
            return back()->with('error', 'Nomor telepon tidak valid.');
        }

        $schoolId = auth()->user()->isSuperAdmin() ? ($request->school_id ?: null) : auth()->user()->school_id;

        // Pastikan nomor yang dipancing sudah pernah berinteraksi/aktivasi sebelumnya (last seen tidak null).
        // Kontak yang belum pernah chat sama sekali tidak boleh dipancing untuk menghindari risiko blokir nomor bot oleh Meta WhatsApp.
        if (!$this->hasContactEverActivated($phone, $schoolId)) {
            return back()->with('error', "Pancing aktivasi dinonaktifkan untuk {$request->name} ({$phone}) karena nomor belum pernah berinteraksi dengan bot WhatsApp. Kontak harus mengirim chat terlebih dahulu ke nomor bot.");
        }

        $msg = "📌 *PEMBERITAHUAN AKTIVASI NOTIFIKASI ABSENSI* 📌\n\n" .
            "Halo Bapak/Ibu/Saudara *{$request->name}* 👋,\n\n" .
            "Masa aktif interaksi WhatsApp Anda pada sistem absensi sekolah perlu diaktifkan agar notifikasi kehadiran dapat masuk dengan lancar.\n\n" .
            "⚠️ *PENTING:* Silakan *BALAS pesan ini* (contoh ketik: *OK* atau *Siap*) agar status nomor Anda terverifikasi aktif oleh server.\n\n" .
            "_Terima kasih atas kerja samanya._ 🙏";

        $delaySeconds = rand(5, 30);
        $mq = new MessageQueue([
            'school_id'    => $schoolId,
            'phone_number' => $phone,
            'message'      => $msg,
            'status'       => 'pending',
            'scheduled_at' => now()->addSeconds($delaySeconds),
            'created_at'   => now(),
        ]);
        $mq->bypass_last_seen = true;
        $mq->save();

        return back()->with('success', "Pesan reminder aktivasi berhasil dimasukkan ke antrean pengiriman untuk {$request->name} ({$phone}).");
    }

    public function clearPending()
    {
        $query = MessageQueue::whereIn('status', ['pending', 'processing', 'failed']);

        if (!auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            } else {
                return back()->with('error', 'Akses ditolak.');
            }
        }

        $count = $query->delete();
        return back()->with('success', "Berhasil menghapus {$count} pesan antrean (pending & failed).");
    }

    public function clearAll()
    {
        $query = MessageQueue::query();

        if (!auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            } else {
                return back()->with('error', 'Akses ditolak.');
            }
        }

        $count = $query->delete();
        return back()->with('success', "Berhasil menghapus semua log antrean WA ({$count} data).");
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function getDailyAttendanceDeliveryRows(?int $schoolId, string $date, int $expiryHours, string $filterRole, string $search): array
    {
        // 1. Ambil seluruh MessageQueue yang dibuat pada tanggal tersebut
        $mqQuery = MessageQueue::whereDate('created_at', $date);
        if ($schoolId) {
            $mqQuery->where('school_id', $schoolId);
        }
        $queues = $mqQuery->get();
        $queueByPhone = [];
        foreach ($queues as $q) {
            $clean = $this->normalizePhone($q->phone_number);
            if ($clean) {
                $queueByPhone[$clean][] = $q;
            }
        }

        $rows = [];

        // 2. Data Absensi Siswa
        if ($filterRole === 'all' || $filterRole === 'siswa' || $filterRole === 'ortu') {
            $siswaAttQuery = Attendance::whereDate('tanggal', $date)
                ->with(['student' => function ($q) {
                    $q->with('kelas');
                }])
                ->orderBy('jam_masuk', 'desc');

            if ($schoolId) {
                $siswaAttQuery->whereHas('student', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            }

            $siswaAttendances = $siswaAttQuery->get();

            foreach ($siswaAttendances as $att) {
                $student = $att->student;
                if (!$student) continue;

                if ($search !== '') {
                    $matchSearch = str_contains(strtolower($student->nama), strtolower($search)) ||
                                   str_contains(strtolower($student->nis ?? ''), strtolower($search)) ||
                                   str_contains(strtolower($student->no_wa ?? ''), strtolower($search)) ||
                                   str_contains(strtolower($student->wa_ortu ?? ''), strtolower($search));
                    if (!$matchSearch) continue;
                }

                $cleanPhoneSiswa = $this->normalizePhone($student->no_wa);
                $cleanPhoneOrtu  = $this->normalizePhone($student->wa_ortu);

                $siswaQueue = $cleanPhoneSiswa && isset($queueByPhone[$cleanPhoneSiswa]) ? end($queueByPhone[$cleanPhoneSiswa]) : null;
                $ortuQueue  = $cleanPhoneOrtu && isset($queueByPhone[$cleanPhoneOrtu]) ? end($queueByPhone[$cleanPhoneOrtu]) : null;

                $siswaIsActive = $cleanPhoneSiswa ? $student->isSiswaWithinLastSeen($expiryHours) : false;
                $ortuIsActive  = $cleanPhoneOrtu ? $student->isOrtuWithinLastSeen($expiryHours) : false;

                $siswaStatus = $this->determineDeliveryStatus($cleanPhoneSiswa, $siswaQueue, $siswaIsActive, $student->last_seen_siswa);
                $ortuStatus  = $this->determineDeliveryStatus($cleanPhoneOrtu, $ortuQueue, $ortuIsActive, $student->last_seen_ortu);

                $isAnyQueued = ($siswaStatus['is_queued'] ?? false) || ($ortuStatus['is_queued'] ?? false);

                $notQueuedReasons = [];
                if (!$isAnyQueued) {
                    if ($siswaStatus['is_skipped']) {
                        $notQueuedReasons[] = 'Siswa: ' . $siswaStatus['label'];
                    } elseif ($siswaStatus['code'] === 'no_phone') {
                        $notQueuedReasons[] = 'No Siswa kosong';
                    }

                    if ($ortuStatus['is_skipped']) {
                        $notQueuedReasons[] = 'Ortu: ' . $ortuStatus['label'];
                    } elseif ($ortuStatus['code'] === 'no_phone') {
                        $notQueuedReasons[] = 'No Ortu kosong';
                    }
                }

                $rows[] = [
                    'id'                 => 'siswa_' . $att->id,
                    'type'               => 'Siswa',
                    'nama'               => $student->nama,
                    'sub_info'           => $student->kelas->nama_kelas ?? '-',
                    'tanggal'            => $att->tanggal,
                    'jam_masuk'          => $att->jam_masuk,
                    'jam_pulang'         => $att->jam_pulang,
                    'status_absen'       => $att->status,
                    'keterangan'         => $att->keterangan,
                    // Status Delivery Global
                    'is_any_queued'      => $isAnyQueued,
                    'not_queued_summary' => !empty($notQueuedReasons) ? implode(' • ', $notQueuedReasons) : 'Tidak masuk antrean',
                    // Penerima Siswa
                    'siswa_phone'        => $student->no_wa,
                    'siswa_last_seen'    => $student->last_seen_siswa,
                    'siswa_is_active'    => $siswaIsActive,
                    'siswa_queue'        => $siswaQueue,
                    'siswa_status'       => $siswaStatus,
                    // Penerima Ortu
                    'ortu_phone'         => $student->wa_ortu,
                    'ortu_last_seen'     => $student->last_seen_ortu,
                    'ortu_is_active'     => $ortuIsActive,
                    'ortu_queue'         => $ortuQueue,
                    'ortu_status'        => $ortuStatus,
                ];
            }
        }

        // 3. Data Absensi Guru
        if ($filterRole === 'all' || $filterRole === 'guru') {
            $guruAttQuery = AbsensiGuru::whereDate('tanggal', $date)
                ->with('guru')
                ->orderBy('jam_masuk', 'desc');

            if ($schoolId) {
                $guruAttQuery->where('school_id', $schoolId);
            }

            $guruAttendances = $guruAttQuery->get();

            foreach ($guruAttendances as $att) {
                $guru = $att->guru;
                if (!$guru) continue;

                if ($search !== '') {
                    $matchSearch = str_contains(strtolower($guru->nama), strtolower($search)) ||
                                   str_contains(strtolower($guru->nip ?? ''), strtolower($search)) ||
                                   str_contains(strtolower($guru->no_wa ?? ''), strtolower($search));
                    if (!$matchSearch) continue;
                }

                $cleanPhoneGuru = $this->normalizePhone($guru->no_wa);
                $guruQueue = $cleanPhoneGuru && isset($queueByPhone[$cleanPhoneGuru]) ? end($queueByPhone[$cleanPhoneGuru]) : null;
                $guruIsActive = $cleanPhoneGuru ? $guru->isWithinLastSeen($expiryHours) : false;

                $guruStatus = $this->determineDeliveryStatus($cleanPhoneGuru, $guruQueue, $guruIsActive, $guru->last_seen);
                $isAnyQueued = $guruStatus['is_queued'] ?? false;

                $notQueuedReasons = [];
                if (!$isAnyQueued) {
                    if ($guruStatus['is_skipped']) {
                        $notQueuedReasons[] = $guruStatus['label'];
                    } elseif ($guruStatus['code'] === 'no_phone') {
                        $notQueuedReasons[] = 'No WA Guru kosong';
                    }
                }

                $rows[] = [
                    'id'                 => 'guru_' . $att->id,
                    'type'               => 'Guru',
                    'nama'               => $guru->nama,
                    'sub_info'           => $guru->nip ? 'NIP: ' . $guru->nip : 'Tenaga Pendidik',
                    'tanggal'            => $att->tanggal,
                    'jam_masuk'          => $att->jam_masuk,
                    'jam_pulang'         => $att->jam_pulang,
                    'status_absen'       => $att->status ?? $att->status_kehadiran ?? 'H',
                    'keterangan'         => $att->keterangan,
                    // Status Delivery Global
                    'is_any_queued'      => $isAnyQueued,
                    'not_queued_summary' => !empty($notQueuedReasons) ? implode(' • ', $notQueuedReasons) : 'Tidak masuk antrean',
                    // Penerima Guru
                    'siswa_phone'        => $guru->no_wa,
                    'siswa_last_seen'    => $guru->last_seen,
                    'siswa_is_active'    => $guruIsActive,
                    'siswa_queue'        => $guruQueue,
                    'siswa_status'       => $guruStatus,
                    // Ortu tidak berlaku untuk guru
                    'ortu_phone'         => null,
                    'ortu_last_seen'     => null,
                    'ortu_is_active'     => false,
                    'ortu_queue'         => null,
                    'ortu_status'        => [
                        'code'       => 'not_applicable',
                        'label'      => 'Bukan Siswa',
                        'detail'     => 'Tidak memerlukan notifikasi ortu',
                        'is_queued'  => false,
                        'is_skipped' => false,
                    ],
                ];
            }
        }

        return $rows;
    }

    private function determineDeliveryStatus(?string $cleanPhone, ?MessageQueue $queue, bool $isActive, $lastSeen = null): array
    {
        if (empty($cleanPhone)) {
            return [
                'code'       => 'no_phone',
                'label'      => 'Nomor Kosong',
                'detail'     => 'Nomor WhatsApp belum diisi di database',
                'is_queued'  => false,
                'is_skipped' => false,
            ];
        }
        if ($queue) {
            $st = $queue->status;
            return [
                'code'       => 'queued_' . $st,
                'label'      => 'Masuk Antrean (' . ucfirst($st) . ')',
                'detail'     => 'Pesan terdata di antrean WA (' . $st . ')',
                'is_queued'  => true,
                'is_skipped' => false,
            ];
        }
        if (empty($lastSeen)) {
            return [
                'code'       => 'skipped_never',
                'label'      => 'Diskip: Belum Pernah Chat',
                'detail'     => 'Kontak belum pernah berinteraksi dengan nomor bot WA sekolah',
                'is_queued'  => false,
                'is_skipped' => true,
            ];
        }
        if (!$isActive) {
            $diffHours = Carbon::parse($lastSeen)->diffInHours(now());
            $diffText = $diffHours > 24 ? floor($diffHours / 24) . ' hari lalu' : max(1, $diffHours) . ' jam lalu';
            return [
                'code'       => 'skipped_expired',
                'label'      => 'Diskip: Last Seen Kadaluarsa',
                'detail'     => 'Terakhir aktif ' . $diffText . ' (melewati batas aturan)',
                'is_queued'  => false,
                'is_skipped' => true,
            ];
        }
        return [
            'code'       => 'not_queued',
            'label'      => 'Tidak Ada Antrean',
            'detail'     => 'Tidak dijadwalkan masuk antrean',
            'is_queued'  => false,
            'is_skipped' => false,
        ];
    }

    private function matchFilterStatus(array $row, string $filter): bool
    {
        if ($filter === 'all') return true;

        if ($filter === 'not_queued') {
            // HANYA yang TIDAK MASUK ANTREAN SAMA SEKALI
            return !$row['is_any_queued'];
        }

        if ($filter === 'queued') {
            return $row['is_any_queued'];
        }

        if ($filter === 'skipped' || $filter === 'skipped_last_seen') {
            return ($row['siswa_status']['is_skipped'] ?? false) || ($row['ortu_status']['is_skipped'] ?? false);
        }

        if ($filter === 'no_phone') {
            return ($row['siswa_status']['code'] ?? '') === 'no_phone' || ($row['ortu_status']['code'] ?? '') === 'no_phone';
        }

        return true;
    }

    private function calculateDailyDeliverySummary(array $rows): array
    {
        $totalAbsen = count($rows);
        $totalQueued = 0;
        $totalNotQueued = 0;
        $totalSkippedLastSeen = 0;
        $totalNoPhone = 0;

        foreach ($rows as $r) {
            if ($r['is_any_queued']) {
                $totalQueued++;
            } else {
                $totalNotQueued++;
                if (($r['siswa_status']['is_skipped'] ?? false) || ($r['ortu_status']['is_skipped'] ?? false)) {
                    $totalSkippedLastSeen++;
                } elseif (($r['siswa_status']['code'] ?? '') === 'no_phone' && (($r['ortu_status']['code'] ?? '') === 'no_phone' || ($r['ortu_status']['code'] ?? '') === 'not_applicable')) {
                    $totalNoPhone++;
                }
            }
        }

        $queuedRate = $totalAbsen > 0 ? round(($totalQueued / $totalAbsen) * 100, 1) : 0.0;

        return [
            'total_absen'             => $totalAbsen,
            'total_queued'            => $totalQueued,
            'total_not_queued'        => $totalNotQueued,
            'total_skipped_last_seen' => $totalSkippedLastSeen,
            'total_no_phone'          => $totalNoPhone,
            'queued_rate'             => $queuedRate,
        ];
    }

    private function getContactsLastSeenList(?int $schoolId, int $expiryHours, string $filterRole, string $filterStatus, string $filterKelas, string $search)
    {
        $allContacts = collect();

        // 1. Siswa & Ortu
        if ($filterRole === 'all' || $filterRole === 'siswa' || $filterRole === 'ortu') {
            $siswaQuery = Siswa::with('kelas');
            if ($schoolId) {
                $siswaQuery->where('school_id', $schoolId);
            }
            if ($filterKelas !== 'all') {
                $siswaQuery->where('kelas_id', $filterKelas);
            }
            if ($search !== '') {
                $siswaQuery->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('nis', 'like', "%{$search}%")
                      ->orWhere('no_wa', 'like', "%{$search}%")
                      ->orWhere('wa_ortu', 'like', "%{$search}%");
                });
            }

            $siswas = $siswaQuery->get();

            foreach ($siswas as $s) {
                // Sebagai Siswa
                if ($filterRole === 'all' || $filterRole === 'siswa') {
                    $allContacts->push($this->formatContactItem(
                        'siswa_' . $s->id,
                        'Siswa',
                        $s->nama,
                        $s->kelas->nama_kelas ?? '-',
                        $s->no_wa,
                        $s->last_seen_siswa,
                        $expiryHours,
                        $s->school_id
                    ));
                }

                // Sebagai Ortu
                if ($filterRole === 'all' || $filterRole === 'ortu') {
                    $allContacts->push($this->formatContactItem(
                        'ortu_' . $s->id,
                        'Orang Tua',
                        'Ortu dari: ' . $s->nama,
                        $s->kelas->nama_kelas ?? '-',
                        $s->wa_ortu,
                        $s->last_seen_ortu,
                        $expiryHours,
                        $s->school_id
                    ));
                }
            }
        }

        // 2. Guru
        if ($filterRole === 'all' || $filterRole === 'guru') {
            $guruQuery = Guru::query();
            if ($schoolId) {
                $guruQuery->where('school_id', $schoolId);
            }
            if ($search !== '') {
                $guruQuery->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('no_wa', 'like', "%{$search}%");
                });
            }

            $gurus = $guruQuery->get();
            foreach ($gurus as $g) {
                $allContacts->push($this->formatContactItem(
                    'guru_' . $g->id,
                    'Guru / Karyawan',
                    $g->nama,
                    $g->nip ? 'NIP: ' . $g->nip : 'Tenaga Pendidik',
                    $g->no_wa,
                    $g->last_seen,
                    $expiryHours,
                    $g->school_id
                ));
            }
        }

        // Filter berdasarkan Status Last Seen jika diminta
        if ($filterStatus !== 'all') {
            $allContacts = $allContacts->filter(function ($item) use ($filterStatus) {
                if ($filterStatus === 'active') return $item['status'] === 'active';
                if ($filterStatus === 'expired') return $item['status'] === 'expired';
                if ($filterStatus === 'never') return $item['status'] === 'never';
                if ($filterStatus === 'no_phone') return $item['status'] === 'no_phone';
                return true;
            });
        }

        // Urutkan: Yang aktif duluan, lalu yang baru saja last seen
        $sorted = $allContacts->sortByDesc(function ($item) {
            return $item['last_seen'] ? $item['last_seen']->timestamp : 0;
        })->values();

        // Paginasi Manual dari Collection
        $page = request()->get('page', 1);
        $perPage = 25;
        $items = $sorted->forPage($page, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function formatContactItem(string $id, string $role, string $nama, string $subInfo, ?string $rawPhone, $lastSeenRaw, int $expiryHours, ?int $schoolId): array
    {
        $cleanPhone = $this->normalizePhone($rawPhone);
        $lastSeen = $lastSeenRaw ? Carbon::parse($lastSeenRaw) : null;

        $status = 'never';
        $remainingText = 'Belum pernah chat';

        if (empty($cleanPhone)) {
            $status = 'no_phone';
            $remainingText = 'Nomor WA kosong';
        } elseif ($lastSeen) {
            $expiryTime = $lastSeen->copy()->addHours($expiryHours);
            $now = now();

            if ($expiryTime->gt($now)) {
                $status = 'active';
                $diffHours = $now->diffInHours($expiryTime);
                if ($diffHours > 24) {
                    $remainingText = 'Aktif (sisa ' . floor($diffHours / 24) . ' hari ' . ($diffHours % 24) . ' jam)';
                } else {
                    $remainingText = 'Aktif (sisa ' . max(1, $diffHours) . ' jam)';
                }
            } else {
                $status = 'expired';
                $diffHours = $expiryTime->diffInHours($now);
                if ($diffHours > 24) {
                    $remainingText = 'Kadaluarsa sejak ' . floor($diffHours / 24) . ' hari lalu';
                } else {
                    $remainingText = 'Kadaluarsa sejak ' . max(1, $diffHours) . ' jam lalu';
                }
            }
        }

        return [
            'id'             => $id,
            'role'           => $role,
            'nama'           => $nama,
            'sub_info'       => $subInfo,
            'phone'          => $cleanPhone ?: $rawPhone,
            'has_phone'      => !empty($cleanPhone),
            'last_seen'      => $lastSeen,
            'status'         => $status,
            'remaining_text' => $remainingText,
            'school_id'      => $schoolId,
        ];
    }

    private function getContactsSummaryStats(?int $schoolId, int $expiryHours): array
    {
        $cutoff = now()->subHours($expiryHours);

        $guruQuery = Guru::whereNotNull('no_wa')->where('no_wa', '!=', '');
        $siswaQuery = Siswa::whereNotNull('no_wa')->where('no_wa', '!=', '');
        $ortuQuery = Siswa::whereNotNull('wa_ortu')->where('wa_ortu', '!=', '');

        if ($schoolId) {
            $guruQuery->where('school_id', $schoolId);
            $siswaQuery->where('school_id', $schoolId);
            $ortuQuery->where('school_id', $schoolId);
        }

        $guruTotal = (clone $guruQuery)->count();
        $guruActive = (clone $guruQuery)->where('last_seen', '>=', $cutoff)->count();
        $guruNever  = (clone $guruQuery)->whereNull('last_seen')->count();
        $guruExpired = $guruTotal - $guruActive - $guruNever;

        $siswaTotal = (clone $siswaQuery)->count();
        $siswaActive = (clone $siswaQuery)->where('last_seen_siswa', '>=', $cutoff)->count();
        $siswaNever  = (clone $siswaQuery)->whereNull('last_seen_siswa')->count();
        $siswaExpired = $siswaTotal - $siswaActive - $siswaNever;

        $ortuTotal = (clone $ortuQuery)->count();
        $ortuActive = (clone $ortuQuery)->where('last_seen_ortu', '>=', $cutoff)->count();
        $ortuNever  = (clone $ortuQuery)->whereNull('last_seen_ortu')->count();
        $ortuExpired = $ortuTotal - $ortuActive - $ortuNever;

        $totalContacts = $guruTotal + $siswaTotal + $ortuTotal;
        $totalActive   = $guruActive + $siswaActive + $ortuActive;
        $totalExpired  = $guruExpired + $siswaExpired + $ortuExpired;
        $totalNever    = $guruNever + $siswaNever + $ortuNever;

        return [
            'total'   => $totalContacts,
            'active'  => $totalActive,
            'expired' => $totalExpired,
            'never'   => $totalNever,
            'active_rate' => $totalContacts > 0 ? round(($totalActive / $totalContacts) * 100, 1) : 0,
        ];
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) return null;
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) return null;
        if (str_starts_with($clean, '0')) {
            return '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '62')) {
            return $clean;
        }
        return '62' . $clean;
    }

    /**
     * Cek apakah kontak pernah memiliki riwayat interaksi (last seen) sebelumnya
     */
    private function hasContactEverActivated(string $phone, ?int $schoolId = null): bool
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) return false;

        $variants = [$clean];
        if (str_starts_with($clean, '62')) {
            $variants[] = '0' . substr($clean, 2);
        } elseif (str_starts_with($clean, '0')) {
            $variants[] = '62' . substr($clean, 1);
        }

        // Cek Guru
        $guruQuery = Guru::whereIn('no_wa', $variants)->whereNotNull('last_seen');
        if ($schoolId) {
            $guruQuery->where('school_id', $schoolId);
        }
        if ($guruQuery->exists()) {
            return true;
        }
        if ($schoolId && Guru::whereIn('no_wa', $variants)->whereNotNull('last_seen')->exists()) {
            return true;
        }

        // Cek Siswa (no_wa)
        $siswaQuery = Siswa::whereIn('no_wa', $variants)->whereNotNull('last_seen_siswa');
        if ($schoolId) {
            $siswaQuery->where('school_id', $schoolId);
        }
        if ($siswaQuery->exists()) {
            return true;
        }
        if ($schoolId && Siswa::whereIn('no_wa', $variants)->whereNotNull('last_seen_siswa')->exists()) {
            return true;
        }

        // Cek Ortu (wa_ortu)
        $ortuQuery = Siswa::whereIn('wa_ortu', $variants)->whereNotNull('last_seen_ortu');
        if ($schoolId) {
            $ortuQuery->where('school_id', $schoolId);
        }
        if ($ortuQuery->exists()) {
            return true;
        }
        if ($schoolId && Siswa::whereIn('wa_ortu', $variants)->whereNotNull('last_seen_ortu')->exists()) {
            return true;
        }

        return false;
    }
}
