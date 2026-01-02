<?php
function handleScan() {
  global $pdo, $uid;

  date_default_timezone_set('Asia/Jakarta');
  $today = date('Y-m-d');
  $now = new DateTime();

  // ======================
  // CARI SISWA
  // ======================
  $stmt = $pdo->prepare("
    SELECT id, nama
    FROM siswa
    WHERE uid_rfid = ?
  ");
  $stmt->execute([$uid]);
  $siswa = $stmt->fetch();

  if (!$siswa) {
    response(false, 'unknown', 'Kartu belum terdaftar', 'error');
  }

  // ======================
  // AMBIL JADWAL HARI INI
  // ======================
  $indexHari = date('N'); // 1=Senin

  $stmt = $pdo->prepare("
    SELECT *
    FROM jadwal
    WHERE index_hari = ?
      AND is_active = 1
    LIMIT 1
  ");
  $stmt->execute([$indexHari]);
  $jadwal = $stmt->fetch();

  if (!$jadwal) {
    response(false, 'gagal', 'Tidak ada jadwal hari ini', 'warning');
  }

  // ======================
  // HITUNG WINDOW
  // ======================
  $jamMasuk = new DateTime("$today {$jadwal['jam_masuk']}");
  $jamPulang = new DateTime("$today {$jadwal['jam_pulang']}");

  $batasMasuk = (clone $jamMasuk)->modify("+{$jadwal['toleransi']} minutes");
  $batasPulang = (clone $jamPulang)->modify("+1 hour");

  // ======================
  // CEK ATTENDANCE HARI INI
  // ======================
  $stmt = $pdo->prepare("
    SELECT *
    FROM attendance
    WHERE student_id = ? AND tanggal = ?
  ");
  $stmt->execute([$siswa['id'], $today]);
  $att = $stmt->fetch();

  // ==================================================
  // ABSEN MASUK
  // ==================================================
  if (!$att) {

    if ($now < $jamMasuk || $now > $batasMasuk) {
      response(false, 'gagal', 'Di luar jam absen masuk', 'warning');
    }

    // hitung keterlambatan
    $status = 'HADIR';
    $keterangan = null;

    if ($now > $jamMasuk) {
      $diff = $now->getTimestamp() - $jamMasuk->getTimestamp();
      if ($diff > 0) {
        $status = 'TERLAMBAT';
        $jam = floor($diff / 3600);
        $menit = floor(($diff % 3600) / 60);
        $keterangan = "Telat {$jam} jam {$menit} menit";
      }
    }

    $pdo->prepare("
      INSERT INTO attendance
      (student_id, tanggal, jam_masuk, status, keterangan)
      VALUES (?, ?, NOW(), ?, ?)
    ")->execute([
      $siswa['id'], $today, $status, $keterangan
    ]);

    response(true, 'success', 'Absen masuk diterima', 'ok', [
      'type' => 'absen_masuk',
      'nama' => $siswa['nama'],
      'status_absen' => $status
    ]);
  }

  // ==================================================
  // ABSEN PULANG
  // ==================================================
  if (!$att['jam_pulang']) {

    if ($now < $jamPulang || $now > $batasPulang) {
      response(false, 'gagal', 'Di luar jam absen pulang', 'warning');
    }

    // hitung total durasi
    $masuk = new DateTime($att['jam_masuk']);
    $totalSeconds = $now->getTimestamp() - $masuk->getTimestamp();

    $pdo->prepare("
      UPDATE attendance
      SET jam_pulang = NOW(),
          total_seconds = ?
      WHERE id = ?
    ")->execute([$totalSeconds, $att['id']]);

    response(true, 'success', 'Absen pulang diterima', 'ok', [
      'type' => 'absen_pulang',
      'nama' => $siswa['nama']
    ]);
  }

  response(false, 'gagal', 'Absensi hari ini sudah lengkap', 'warning');
}
