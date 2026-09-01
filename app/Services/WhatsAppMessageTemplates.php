<?php

namespace App\Services;

use Carbon\Carbon;

class WhatsAppMessageTemplates
{
    /**
     * Salam pembuka yang bervariasi — deterministik per nama+tanggal,
     * sehingga orang yang sama mendapat salam berbeda setiap hari.
     */
    private static function randomGreeting(string $nama, bool $isParent = false): string
    {
        $seed = crc32($nama . date('Y-m-d')) % 5;

        if ($isParent) {
            $greetings = [
                "Halo, Orang Tua/Wali dari *{$nama}*,",
                "Yth. Orang Tua/Wali *{$nama}*,",
                "Salam hormat, Orang Tua/Wali dari *{$nama}*,",
                "Kepada Orang Tua/Wali *{$nama}*,",
                "Halo, Bapak/Ibu wali dari *{$nama}*,",
            ];
        } else {
            $greetings = [
                "Halo, *{$nama}* 👋,",
                "Hai, *{$nama}*!",
                "Assalamu'alaikum, *{$nama}* 🌟,",
                "Halo *{$nama}*, semangat hari ini! 💪",
                "Selamat pagi/siang *{$nama}* 👋,",
            ];
        }

        return $greetings[abs($seed)];
    }

    /**
     * Kalimat penutup yang bervariasi — deterministik per nama+tanggal.
     * Mengarahkan penerima agar membalas pesan untuk meningkatkan reputasi nomor pengirim di server WA.
     */
    private static function randomClosing(string $nama, bool $isParent = false): string
    {
        $seed = crc32('closing_' . $nama . date('Y-m-d')) % 5;

        if ($isParent) {
            $closings = [
                "_\n\nSilakan balas pesan ini dengan kata *OK* atau *Diterima* sebagai konfirmasi Anda._",
                "_\n\nMohon berkenan membalas chat ini singkat saja (contoh: *Diterima*) sebagai tanda laporan telah dibaca._",
                "_\n\nHarap balas pesan otomatis ini untuk memastikan laporan absensi anak Anda terkirim dengan baik._",
                "_\n\nSilakan ketik balas *OK* pada chat ini agar sistem mencatat tanda terima orang tua._",
                "_\n\nMohon balas pesan ini singkat saja untuk menjaga kelancaran saluran komunikasi absensi sekolah._",
            ];
        } else {
            $closings = [
                "_\n\nSilakan balas pesan ini singkat saja (contoh: *OK*) untuk konfirmasi Anda._",
                "_\n\nMohon balas chat ini sebagai tanda bahwa pemberitahuan ini telah dibaca._",
                "_\n\nHarap ketik balas *Diterima/OK* untuk memastikan pesan absensi Anda telah sampai._",
                "_\n\nSilakan balas pesan otomatis ini dengan ketikan singkat._",
                "_\n\nMohon berkenan membalas chat ini untuk verifikasi penerimaan pesan._",
            ];
        }

        return $closings[abs($seed)];
    }

    /**
     * Check-in notification (Hadir, Izin, Sakit)
     */
    public static function checkIn(string $nama, string $jamMasuk, string $kelas, string $status = 'Hadir'): string
    {
        $tgl = now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama);
        $closing  = self::randomClosing($nama, isParent: false);
        $seed = crc32($nama . date('Y-m-d')) % 3;

        // Sakit template variations
        if (str_contains(strtolower($status), 'sakit')) {
            $options = [
                "🤒 *Pemberitahuan Sakit*\n\n{$greeting}\n\nStatus absensi Anda tercatat *Sakit* hari ini. Semoga lekas sembuh ya! ❤️\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
                "🔔 *Konfirmasi Izin Sakit*\n\n{$greeting}\n\nTercatat tidak masuk sekolah hari ini karena *Sakit*. Istirahat yang cukup dan semoga cepat pulih! 🤲\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
                "📋 *Catatan Sakit Harian*\n\n{$greeting}\n\nSistem memperbarui absensi Anda hari ini dengan keterangan *Sakit*.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
            ];
            return $options[abs($seed)];
        }

        // Izin template variations
        if (str_contains(strtolower($status), 'izin')) {
            $options = [
                "📝 *Pemberitahuan Izin*\n\n{$greeting}\n\nStatus absensi Anda tercatat *Izin* hari ini.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
                "🔔 *Konfirmasi Permohonan Izin*\n\n{$greeting}\n\nPengajuan izin sekolah Anda telah dikonfirmasi oleh sistem absensi.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n📊 Status: Izin\n\n{$closing}",
                "📋 *Laporan Izin Harian*\n\n{$greeting}\n\nTercatat tidak masuk sekolah dengan keterangan *Izin*.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
            ];
            return $options[abs($seed)];
        }

        // Standard Hadir / Check-in variations
        $options = [
            "✅ *Konfirmasi Absen Masuk*\n\n{$greeting}\n\nTercatat masuk sekolah hari ini:\n📅 Tanggal  : {$tgl}\n🕐 Jam Masuk: {$jamMasuk}\n📊 Keterangan: {$status}\n🏫 Kelas    : {$kelas}\n\n{$closing}",
            "🔔 *Kehadiran Absensi Masuk*\n\n{$greeting}\n\nAnda telah sukses absen masuk sekolah pada pukul {$jamMasuk}.\n📅 Tanggal  : {$tgl}\n🏫 Kelas    : {$kelas}\n📊 Status   : {$status}\n\n{$closing}",
            "📝 *Catatan Masuk Harian*\n\n{$greeting}\n\nAbsensi masuk Anda telah masuk ke sistem.\n📅 Tanggal  : {$tgl}\n🕐 Pukul    : {$jamMasuk}\n🏫 Kelas    : {$kelas}\n📊 Status   : {$status}\n\n{$closing}"
        ];
        return $options[abs($seed)];
    }

    /**
     * Check-out notification
     */
    public static function checkOut(
        string $nama,
        string $jamMasuk,
        string $jamPulang,
        int $hours,
        int $minutes,
        string $authorizedBy,
        ?string $tanggal = null
    ): string {
        $tgl      = $tanggal ?? now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama);
        $closing  = self::randomClosing($nama, isParent: false);
        $seed = crc32('checkout_' . $nama . $tgl) % 3;

        $options = [
            "🏠 *Notifikasi Absen Pulang*\n\n{$greeting}\n\nTercatat selesai pelajaran dan absen pulang:\n📅 Tanggal     : {$tgl}\n🕐 Jam Masuk   : {$jamMasuk}\n🕐 Jam Pulang  : {$jamPulang}\n⏱️ Durasi      : {$hours} jam {$minutes} menit\n👤 Diotorisasi : {$authorizedBy}\n\nHati-hati di jalan saat pulang ke rumah! 🏠\n\n{$closing}",
            "🚪 *Konfirmasi Absen Pulang*\n\n{$greeting}\n\nAnda telah absen keluar sekolah hari ini.\n📅 Tanggal     : {$tgl}\n🕐 Jam Pulang  : {$jamPulang}\n⏱️ Durasi      : {$hours} jam {$minutes} menit\n👤 Diotorisasi : {$authorizedBy}\n\nSemoga ilmu hari ini bermanfaat. Hati-hati di jalan! ✨\n\n{$closing}",
            "📋 *Catatan Absensi Keluar*\n\n{$greeting}\n\nAbsen pulang sekolah Anda telah berhasil terekam.\n📅 Tanggal     : {$tgl}\n🕐 Waktu       : {$jamPulang}\n⏱️ Total Durasi: {$hours} jam {$minutes} menit\n👤 Otorisasi   : {$authorizedBy}\n\nSampai jumpa besok di sekolah dengan semangat baru! 💪\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Late check-in notification
     */
    public static function checkInLate(
        string $nama,
        string $jamMasuk,
        string $kelas,
        int $lateHours = 0,
        int $lateMinutes = 0
    ): string {
        $tgl = now()->format('d/m/Y');
        // Format durasi: "1 jam 30 menit", "30 menit", atau "1 jam"
        if ($lateHours > 0 && $lateMinutes > 0) {
            $lateDuration = "{$lateHours} jam {$lateMinutes} menit";
        } elseif ($lateHours > 0) {
            $lateDuration = "{$lateHours} jam";
        } else {
            $lateDuration = "{$lateMinutes} menit";
        }

        $greeting = self::randomGreeting($nama);
        $closing  = self::randomClosing($nama, isParent: false);
        $seed = crc32('late_' . $nama . $tgl) % 3;

        $options = [
            "⚠️ *Notifikasi Terlambat*\n\n{$greeting}\n\nAnda terdeteksi absen masuk pada pukul {$jamMasuk}.\n📅 Tanggal       : {$tgl}\n⏰ Keterlambatan : {$lateDuration}\n📊 Status        : Terlambat\n🏫 Kelas         : {$kelas}\n\nMohon lebih disiplin waktu kedepannya ya. 🙏\n\n{$closing}",
            "🔔 *Pemberitahuan Keterlambatan*\n\n{$greeting}\n\nAbsensi masuk Anda tercatat terlambat pada pukul {$jamMasuk}.\n📅 Tanggal       : {$tgl}\n⏰ Selisih Waktu : {$lateDuration}\n🏫 Kelas         : {$kelas}\n\nSilakan usahakan datang lebih awal di hari berikutnya. 💪\n\n{$closing}",
            "📋 *Catatan Terlambat Masuk*\n\n{$greeting}\n\nSistem mencatat Anda terlambat masuk sekolah hari ini.\n📅 Tanggal       : {$tgl}\n🕐 Waktu Scan    : {$jamMasuk}\n⏰ Durasi Telat  : {$lateDuration}\n🏫 Kelas         : {$kelas}\n\nMari tingkatkan kedisiplinan demi masa depan. 🙏\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Check-in notification to parent
     */
    public static function checkInParent(string $nama, string $jamMasuk, string $kelas, string $status = 'Hadir'): string
    {
        $tgl = now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama, isParent: true);
        $closing  = self::randomClosing($nama, isParent: true);
        $seed = crc32('parent_in_' . $nama . $tgl) % 3;

        // Sakit template variations to parent
        if (str_contains(strtolower($status), 'sakit')) {
            $options = [
                "🤒 *Laporan Izin Sakit Anak*\n\n{$greeting}\n\nMenginfokan bahwa anak Anda tercatat tidak masuk sekolah hari ini karena *Sakit*. Semoga lekas sembuh dan lekas ceria kembali. 🤲\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
                "🔔 *Pemberitahuan Siswa Sakit*\n\n{$greeting}\n\nPutra/putri Anda hari ini izin tidak masuk sekolah karena *Sakit*. Kami mendoakan semoga lekas pulih. ❤️\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
                "📋 *Catatan Sakit Anak*\n\n{$greeting}\n\nSistem kehadiran mencatat status absen anak Anda hari ini adalah *Sakit*.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
            ];
            return $options[abs($seed)];
        }

        // Izin template variations to parent
        if (str_contains(strtolower($status), 'izin')) {
            $options = [
                "📝 *Laporan Izin Anak*\n\n{$greeting}\n\nMenginfokan bahwa anak Anda tercatat tidak masuk sekolah dengan keterangan *Izin*.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
                "🔔 *Pemberitahuan Izin Sekolah*\n\n{$greeting}\n\nPermohonan izin putra/putri Anda hari ini telah diterima dan dicatat dalam absensi.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
                "📋 *Catatan Izin Anak*\n\n{$greeting}\n\nSistem mencatat status kehadiran anak Anda hari ini adalah *Izin*.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n\n{$closing}",
            ];
            return $options[abs($seed)];
        }

        // Standard Hadir check-in to parent
        $options = [
            "✅ *Laporan Absen Masuk Anak*\n\n{$greeting}\n\nAnak Anda telah tercatat hadir di sekolah.\n📅 Tanggal  : {$tgl}\n🕐 Jam Masuk: {$jamMasuk}\n📊 Status   : {$status}\n🏫 Kelas    : {$kelas}\n\n{$closing}",
            "🔔 *Kehadiran Sekolah Anak*\n\n{$greeting}\n\nMenginfokan bahwa anak Anda telah melakukan absen masuk pada pukul {$jamMasuk}.\n📅 Tanggal  : {$tgl}\n🏫 Kelas    : {$kelas}\n📊 Keterangan: {$status}\n\n{$closing}",
            "📋 *Catatan Kehadiran Anak*\n\n{$greeting}\n\nSistem mencatat putra/putri Anda tiba di sekolah dan absen masuk.\n📅 Tanggal  : {$tgl}\n🕐 Pukul    : {$jamMasuk}\n📊 Status   : {$status}\n🏫 Kelas    : {$kelas}\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Late check-in notification to parent
     */
    public static function checkInLateParent(
        string $nama,
        string $jamMasuk,
        string $kelas,
        int $lateHours = 0,
        int $lateMinutes = 0
    ): string {
        $tgl = now()->format('d/m/Y');
        if ($lateHours > 0 && $lateMinutes > 0) {
            $lateDuration = "{$lateHours} jam {$lateMinutes} menit";
        } elseif ($lateHours > 0) {
            $lateDuration = "{$lateHours} jam";
        } else {
            $lateDuration = "{$lateMinutes} menit";
        }

        $greeting = self::randomGreeting($nama, isParent: true);
        $closing  = self::randomClosing($nama, isParent: true);
        $seed = crc32('parent_late_' . $nama . $tgl) % 3;

        $options = [
            "⚠️ *Notifikasi Terlambat Anak*\n\n{$greeting}\n\nAnak Anda telah tercatat hadir di sekolah, namun *terlambat*.\n📅 Tanggal       : {$tgl}\n🕐 Jam Masuk     : {$jamMasuk}\n⏰ Keterlambatan : {$lateDuration}\n📊 Status        : Terlambat\n🏫 Kelas         : {$kelas}\n\nMohon bantuannya untuk mengingatkan anak agar lebih disiplin. 🙏\n\n{$closing}",
            "🔔 *Laporan Keterlambatan Anak*\n\n{$greeting}\n\nPutra/putri Anda absen masuk sekolah pukul {$jamMasuk} dengan keterangan terlambat.\n📅 Tanggal       : {$tgl}\n⏰ Selisih Waktu : {$lateDuration}\n🏫 Kelas         : {$kelas}\n\nMohon kerjasamanya untuk memotivasi anak datang tepat waktu. Terima kasih. 🤝\n\n{$closing}",
            "📋 *Catatan Kehadiran Terlambat*\n\n{$greeting}\n\nMenginfokan bahwa anak Anda tiba di sekolah melebihi batas waktu.\n📅 Tanggal       : {$tgl}\n🕐 Jam Masuk     : {$jamMasuk}\n⏰ Durasi Telat  : {$lateDuration}\n🏫 Kelas         : {$kelas}\n\nHarap diingatkan kembali mengenai jam masuk sekolah. 🙏\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Check-out notification to parent
     */
    public static function checkOutParent(
        string $nama,
        string $jamMasuk,
        string $jamPulang,
        int $hours,
        int $minutes,
        string $authorizedBy,
        ?string $tanggal = null
    ): string {
        $tgl      = $tanggal ?? now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama, isParent: true);
        $closing  = self::randomClosing($nama, isParent: true);
        $seed = crc32('parent_out_' . $nama . $tgl) % 3;

        $options = [
            "🏠 *Notifikasi Absen Pulang Anak*\n\n{$greeting}\n\nAnak Anda telah tercatat pulang dari sekolah.\n📅 Tanggal     : {$tgl}\n🕐 Jam Masuk   : {$jamMasuk}\n🕐 Jam Pulang  : {$jamPulang}\n⏱️ Durasi      : {$hours} jam {$minutes} menit\n👤 Diotorisasi : {$authorizedBy}\n\n{$closing}",
            "🚪 *Laporan Absen Keluar Anak*\n\n{$greeting}\n\nPutra/putri Anda telah selesai melakukan scan absen pulang.\n📅 Tanggal     : {$tgl}\n🕐 Jam Pulang  : {$jamPulang}\n⏱️ Durasi      : {$hours} jam {$minutes} menit\n👤 Pengawas    : {$authorizedBy}\n\nSemoga anak Anda selamat sampai di rumah. 🤝\n\n{$closing}",
            "📋 *Catatan Kepulangan Anak*\n\n{$greeting}\n\nSistem mencatat anak Anda telah keluar dari gerbang sekolah.\n📅 Tanggal     : {$tgl}\n🕐 Jam Masuk   : {$jamMasuk}\n🕐 Jam Pulang  : {$jamPulang}\n⏱️ Total Durasi: {$hours} jam {$minutes} menit\n👤 Otorisasi   : {$authorizedBy}\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Alpha (absent) notification to student
     */
    public static function alphaStudent(string $nama): string
    {
        $tgl = now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama);
        $closing  = self::randomClosing($nama, isParent: false);
        $seed = crc32('alpha_' . $nama . $tgl) % 3;

        $options = [
            "❌ *Pemberitahuan Ketidakhadiran*\n\n{$greeting}\n\n📅 Tanggal: {$tgl}\n📊 Status: Alpha (Tidak Hadir)\n\nAnda tercatat tidak hadir hari ini tanpa keterangan.\nMohon segera konfirmasi ke wali kelas atau bagian kesiswaan.\n\n{$closing}",
            "🚨 *Laporan Absen - Tidak Hadir*\n\n{$greeting}\n\nHari ini Anda terdata tidak mengikuti KBM tanpa surat izin/keterangan.\n📅 Hari/Tanggal: {$tgl}\n📊 Status Kehadiran: Alpha (A)\n\nHarap serahkan surat keterangan dokter/orang tua ke wali kelas jika Anda berhalangan hadir. 📨\n\n{$closing}",
            "⚠️ *Catatan Ketidakhadiran Absensi*\n\n{$greeting}\n\nSistem mendeteksi Anda tidak melakukan absensi masuk hari ini.\n📅 Tanggal: {$tgl}\n📊 Keterangan: Tanpa Keterangan (Alpha)\n\nSegera hubungi pihak sekolah untuk konfirmasi kehadiran Anda. 🙏\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Alpha (absent) notification to parent
     */
    public static function alphaParent(string $nama, string $kelas): string
    {
        $tgl = now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama, isParent: true);
        $closing  = self::randomClosing($nama, isParent: true);
        $seed = crc32('parent_alpha_' . $nama . $tgl) % 3;

        $options = [
            "❌ *Pemberitahuan Ketidakhadiran Anak*\n\n{$greeting}\n\n📅 Tanggal: {$tgl}\n📊 Status: Alpha (Tidak Hadir)\n⚠️ Kelas: {$kelas}\n\nAnak Anda tercatat tidak hadir hari ini tanpa keterangan.\nMohon konfirmasi kepada wali kelas atau bagian kesiswaan.\n\n{$closing}",
            "🚨 *Laporan Anak Absen Tanpa Keterangan*\n\n{$greeting}\n\nPutra/putri Anda hari ini terdata tidak hadir di sekolah tanpa pemberitahuan.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n📊 Status: Alpha (A)\n\nMohon segera konfirmasi ke wali kelas jika anak Anda sedang sakit atau berhalangan hadir. 🤝\n\n{$closing}",
            "⚠️ *Laporan Ketidakhadiran Sekolah*\n\n{$greeting}\n\nMenginfokan bahwa anak Anda tidak melakukan absensi masuk hingga batas waktu absen.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n📊 Keterangan: Alpha\n\nMohon hubungi wali kelas jika terdapat kekeliruan data kehadiran anak. 🙏\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Bolos (skipped checkout) notification to student
     */
    public static function bolosStudent(string $nama): string
    {
        $tgl = now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama);
        $closing  = self::randomClosing($nama, isParent: false);
        $seed = crc32('bolos_' . $nama . $tgl) % 3;

        $options = [
            "🏃 *Pemberitahuan Indikasi Bolos*\n\n{$greeting}\n\n📅 Tanggal: {$tgl}\n📊 Status: Bolos (Tidak Absen Pulang)\n\nAnda tercatat sudah absen masuk, namun tidak melakukan absen pulang hingga batas waktu yang ditentukan.\nMohon segera konfirmasi ke wali kelas atau bagian kesiswaan jika Anda lupa melakukan absen pulang.\n\n{$closing}",
            "🚨 *Peringatan Ketidakhadiran Pulang*\n\n{$greeting}\n\nSistem mendeteksi bahwa Anda tidak melakukan absen pulang (check-out) hari ini.\n📅 Hari/Tanggal: {$tgl}\n📊 Status Kehadiran: Bolos (B)\n\nJika ini adalah kekeliruan (lupa tempel kartu), segera hubungi wali kelas Anda. 🙏\n\n{$closing}",
            "⚠️ *Catatan Tanpa Absen Keluar*\n\n{$greeting}\n\nStatus absensi Anda diubah menjadi Bolos karena tidak melakukan absen pulang.\n📅 Tanggal: {$tgl}\n📊 Keterangan: Bolos\n\nHarap disiplin untuk selalu menempelkan kartu saat pulang sekolah. 🤝\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Bolos (skipped checkout) notification to parent
     */
    public static function bolosParent(string $nama, string $kelas): string
    {
        $tgl = now()->format('d/m/Y');
        $greeting = self::randomGreeting($nama, isParent: true);
        $closing  = self::randomClosing($nama, isParent: true);
        $seed = crc32('parent_bolos_' . $nama . $tgl) % 3;

        $options = [
            "🏃 *Pemberitahuan Indikasi Bolos Anak*\n\n{$greeting}\n\n📅 Tanggal: {$tgl}\n📊 Status: Bolos (Tidak Absen Pulang)\n⚠️ Kelas: {$kelas}\n\nAnak Anda tercatat sudah absen masuk hari ini, namun tidak melakukan absen pulang hingga batas waktu yang ditentukan.\nMohon konfirmasi kepada anak Anda atau wali kelas terkait hal ini.\n\n{$closing}",
            "🚨 *Laporan Anak Tidak Absen Pulang*\n\n{$greeting}\n\nPutra/putri Anda terdeteksi tidak menempelkan kartu absen saat jam kepulangan.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n📊 Status: Bolos (B)\n\nMohon kerjasamanya untuk menanyakan keberadaan anak Anda saat ini. Terima kasih. 🤝\n\n{$closing}",
            "⚠️ *Laporan Indikasi Bolos Kehadiran*\n\n{$greeting}\n\nSistem mencatat anak Anda memiliki riwayat masuk tapi tidak memiliki riwayat absen keluar.\n📅 Tanggal: {$tgl}\n🏫 Kelas: {$kelas}\n📊 Status: Bolos\n\nMohon lakukan klarifikasi kepada pihak sekolah jika terdapat kendala kartu/alat scan. 🙏\n\n{$closing}"
        ];

        return $options[abs($seed)];
    }

    /**
     * Daily report for class group
     */
    public static function dailyReportClass(
        string $namaKelas,
        int $masuk,
        int $tidakMasuk,
        array $absentByStatus
    ): string {
        $msg = "📊 *Laporan Absensi Kelas {$namaKelas}*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Masuk: {$masuk}\n";

        $terlambat = isset($absentByStatus['T']) ? count($absentByStatus['T']) : 0;
        if ($terlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: {$terlambat}\n";
        }

        $msg .= "❌ Siswa Tidak Masuk: {$tidakMasuk}\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        if ($tidakMasuk > 0 || $terlambat > 0) {
            $statusLabels = [
                'T' => '⚠️ Terlambat',
                'A' => '❌ Alpha',
                'I' => '📝 Izin',
                'S' => '🤒 Sakit',
                'B' => '🏃 Bolos'
            ];

            foreach (['T', 'A', 'I', 'S', 'B'] as $status) {
                if (empty($absentByStatus[$status])) {
                    continue;
                }

                $count = count($absentByStatus[$status]);
                $msg .= "*{$statusLabels[$status]}* ({$count} siswa)\n";
                foreach ($absentByStatus[$status] as $nama) {
                    $msg .= "  • {$nama}\n";
                }
                $msg .= "\n";
            }
        } else {
            $msg .= "🎉 *Nihil (Semua Masuk)*\n\n";
        }

        $msg .= "_Generated by System_";
        return $msg;
    }

    /**
     * Daily report for homeroom teacher (wali kelas)
     */
    public static function dailyReportWaliKelas(
        string $namaKelas,
        string $namaWali,
        int $masuk,
        int $tidakMasuk,
        array $listAbsen,
        array $listTerlambat = [],
        array $listKegiatan = []
    ): string {
        $msg = "📊 *Laporan Absensi Kelas {$namaKelas}*\n";
        $msg .= "👤 Wali Kelas: {$namaWali}\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= "---------------------------\n";
        $msg .= "✅ Hadir: {$masuk}\n";
        $terlambat = count($listTerlambat);
        if ($terlambat > 0) {
            $msg .= "⚠️ Terlambat: {$terlambat}\n";
        }
        $msg .= "❌ Tidak Hadir: {$tidakMasuk}\n";
        $msg .= "---------------------------\n";

        if (!empty($listKegiatan)) {
            $msg .= "🎯 *Rekap Kehadiran Kegiatan Hari Ini:*\n";
            foreach ($listKegiatan as $keg) {
                $detailStr = "{$keg['hadir']} Hadir";
                if (!empty($keg['izin'])) $detailStr .= ", {$keg['izin']} Izin";
                if (!empty($keg['sakit'])) $detailStr .= ", {$keg['sakit']} Sakit";
                $msg .= "• *{$keg['nama']}*: {$detailStr}\n";
            }
            $msg .= "---------------------------\n";
        }

        if ($terlambat > 0) {
            $msg .= "*Detail Terlambat:*\n";
            foreach ($listTerlambat as $item) {
                $msg .= "- {$item}\n";
            }
            $msg .= "\n";
        }

        if ($tidakMasuk > 0) {
            $msg .= "*Detail Tidak Hadir:*\n";
            foreach ($listAbsen as $item) {
                $msg .= "- {$item}\n";
            }
        }

        if ($tidakMasuk == 0 && $terlambat == 0) {
            $msg .= "🎉 *Nihil (Semua Masuk Tepat Waktu)*\n";
        }

        $msg .= "\n_Generated by System_";
        return $msg;
    }

    /**
     * Global daily report (all classes)
     */
    public static function dailyReportGlobal(
        int $totalMasuk,
        int $totalTidakMasuk,
        array $absentByStatus,
        array $statsByJurusan = [],
        array $listKegiatan = []
    ): string {
        $msg = "📊 *Laporan Absensi Harian*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Masuk: {$totalMasuk}\n";
        $totalTerlambat = isset($absentByStatus['T']) ? count($absentByStatus['T']) : 0;
        if ($totalTerlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: {$totalTerlambat}\n";
        }
        $msg .= "❌ Siswa Tidak Masuk: {$totalTidakMasuk}\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        if (!empty($listKegiatan)) {
            $msg .= "🎯 *Rekap Kehadiran Kegiatan Hari Ini:*\n";
            foreach ($listKegiatan as $keg) {
                $detailStr = "{$keg['hadir']} Hadir";
                if (!empty($keg['izin'])) $detailStr .= ", {$keg['izin']} Izin";
                if (!empty($keg['sakit'])) $detailStr .= ", {$keg['sakit']} Sakit";
                $msg .= "• *{$keg['nama']}*: {$detailStr}\n";
            }
            $msg .= str_repeat("─", 30) . "\n\n";
        }

        if ($totalTidakMasuk > 0 || $totalTerlambat > 0) {
            $statusLabels = [
                'T' => '⚠️ Terlambat',
                'A' => '❌ Alpha',
                'I' => '📝 Izin',
                'S' => '🤒 Sakit',
                'B' => '🏃 Bolos'
            ];

            foreach (['T', 'A', 'I', 'S', 'B'] as $status) {
                if (empty($absentByStatus[$status])) {
                    continue;
                }

                $count = count($absentByStatus[$status]);
                $msg .= "*{$statusLabels[$status]}*: {$count} siswa\n";
            }
        } else {
            $msg .= "🎉 *Nihil (Semua Masuk)*\n";
        }

        if (!empty($statsByJurusan)) {
            $msg .= "------\n";
            foreach ($statsByJurusan as $jurusan => $kelasData) {
                $msg .= "*Jurusan {$jurusan}*\n";
                foreach ($kelasData as $kelas => $stats) {
                    $msg .= "{$kelas}\n";
                    $msg .= "Total Siswa : {$stats['total']}\n";
                    $msg .= "Hadir : {$stats['H']}\n";
                    $msg .= "Terlambat: {$stats['T']}\n";
                    $msg .= "Alpha : {$stats['A']}\n";
                    $msg .= "Sakit : {$stats['S']}\n";
                    $msg .= "Izin : {$stats['I']}\n";
                    $msg .= "Bolos : {$stats['B']}\n\n";
                }
            }
        }

        $msg .= "Generated by System\n\n";

        return $msg;
    }

    /**
     * Final absence report (after daily processing)
     */
    public static function finalAbsenceReport(
        int $totalPresent,
        int $totalAbsent,
        iterable $absentStudentsGrouped
    ): string {
        $msg = "📋 *LAPORAN FINAL ABSENSI*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Hadir: *{$totalPresent}*\n";
        $terlambat = isset($absentStudentsGrouped['T']) ? count($absentStudentsGrouped['T']) : 0;
        if ($terlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: *{$terlambat}*\n";
        }
        $msg .= "❌ Siswa Tidak Hadir: *{$totalAbsent}*\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        $statusLabels = [
            'T' => '⚠️ Terlambat',
            'A' => '❌ Alpha',
            'B' => '🏃 Bolos (Tidak Absen Pulang)',
            'I' => '📝 Izin',
            'S' => '🤒 Sakit'
        ];

        $hasDetails = false;
        foreach (['T', 'A', 'B', 'I', 'S'] as $status) {
            if (!isset($absentStudentsGrouped[$status])) {
                continue;
            }

            $students = $absentStudentsGrouped[$status];
            $count = is_countable($students) ? count($students) : iterator_count($students);
            if ($count > 0) {
                $hasDetails = true;
                $msg .= "*{$statusLabels[$status]}* ({$count} siswa)\n";
                foreach ($students as $att) {
                    $kelas = $att->student->kelas->nama_kelas ?? '-';
                    $msg .= "  • {$att->student->nama} ({$kelas})\n";
                }
                $msg .= "\n";
            }
        }

        if (!$hasDetails) {
            $msg .= "🎉 *Nihil (Semua Hadir)*\n\n";
        }

        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "\n_Laporan otomatis setelah proses harian_";

        return $msg;
    }

    /**
     * Final absence report global (after daily processing)
     */
    public static function finalAbsenceReportGlobal(
        int $totalPresent,
        int $totalAbsent,
        iterable $absentStudentsGrouped,
        array $statsByJurusan = []
    ): string {
        $msg = "📋 *LAPORAN FINAL ABSENSI*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Hadir: *{$totalPresent}*\n";
        $terlambat = isset($absentStudentsGrouped['T']) ? count($absentStudentsGrouped['T']) : 0;
        if ($terlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: *{$terlambat}*\n";
        }
        $msg .= "❌ Siswa Tidak Hadir: *{$totalAbsent}*\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        $statusLabels = [
            'T' => '⚠️ Terlambat',
            'A' => '❌ Alpha',
            'B' => '🏃 Bolos (Tidak Absen Pulang)',
            'I' => '📝 Izin',
            'S' => '🤒 Sakit'
        ];

        $hasDetails = false;
        foreach (['T', 'A', 'B', 'I', 'S'] as $status) {
            if (!isset($absentStudentsGrouped[$status])) {
                continue;
            }

            $students = $absentStudentsGrouped[$status];
            $count = is_countable($students) ? count($students) : iterator_count($students);
            if ($count > 0) {
                $hasDetails = true;
                $msg .= "*{$statusLabels[$status]}*: {$count} siswa\n";
            }
        }

        if (!$hasDetails) {
            $msg .= "🎉 *Nihil (Semua Hadir)*\n";
        }

        if (!empty($statsByJurusan)) {
            $msg .= "\n------\n";
            foreach ($statsByJurusan as $jurusan => $kelasData) {
                $msg .= "*Jurusan {$jurusan}*\n";
                foreach ($kelasData as $kelas => $stats) {
                    $msg .= "{$kelas}\n";
                    $msg .= "Total Siswa : {$stats['total']}\n";
                    $msg .= "Hadir : {$stats['H']}\n";
                    $msg .= "Terlambat: {$stats['T']}\n";
                    $msg .= "Alpa : {$stats['A']}\n";
                    $msg .= "Sakit : {$stats['S']}\n";
                    $msg .= "Izin : {$stats['I']}\n";
                    $msg .= "Bolos : {$stats['B']}\n\n";
                }
            }
        }

        $msg .= "Generated by System\n\n";

        return $msg;
    }

    /**
     * Abnormal attendance alert (frequent absences)
     */
    public static function abnormalAttendanceAlert(
        string $nama,
        string $kelas,
        int $alphaCount,
        int $bolosCount,
        int $totalDays,
        string $periodStart,
        string $periodEnd
    ): string {
        $msg = "⚠️ *PERINGATAN KETIDAKHADIRAN BERLEBIHAN*\n\n";
        $msg .= "Siswa: *{$nama}*\n";
        $msg .= "Kelas: {$kelas}\n";
        $msg .= "Periode: {$periodStart} - {$periodEnd}\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "❌ Alpha: {$alphaCount} hari\n";
        $msg .= "🏃 Bolos: {$bolosCount} hari\n";
        $msg .= "📊 Total Ketidakhadiran: " . ($alphaCount + $bolosCount) . " dari {$totalDays} hari\n\n";
        $msg .= "Mohon segera ditindaklanjuti oleh wali kelas dan orang tua.\n\n";
        $msg .= "_Notifikasi otomatis dari sistem monitoring kehadiran._";

        return $msg;
    }

    /**
     * Teacher schedule notification
     */
    public static function teacherSchedule(string $namaGuru, array $jadwalHariIni): string
    {
        $msg = "📚 *Jadwal Mengajar Hari Ini*\n\n";
        $msg .= "Halo, *{$namaGuru}*,\n\n";
        $msg .= "📅 " . now()->locale('id')->isoFormat('dddd, D MMMM YYYY') . "\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        if (empty($jadwalHariIni)) {
            $msg .= "Tidak ada jadwal mengajar hari ini.\n\n";
        } else {
            foreach ($jadwalHariIni as $jadwal) {
                $msg .= "🕐 {$jadwal['jam_mulai']} - {$jadwal['jam_selesai']}\n";
                $msg .= "📖 {$jadwal['mata_pelajaran']}\n";
                $msg .= "🏫 Kelas: {$jadwal['kelas']}\n\n";
            }
        }

        $msg .= "_Semangat mengajar!_\n";
        $msg .= "_Notifikasi otomatis dari sistem._";

        return $msg;
    }

    /**
     * Broadcast message template
     */
    public static function broadcast(string $title, string $message, ?string $footer = null): string
    {
        $msg = "📢 *{$title}*\n\n";
        $msg .= $message . "\n\n";

        if ($footer) {
            $msg .= "_{$footer}_";
        } else {
            $msg .= "_Pengumuman dari sistem sekolah._";
        }

        return $msg;
    }

    /**
     * Kegiatan check-in notification (siswa atau ortu)
     */
    public static function kegiatanCheckIn(
        string $nama,
        string $namaKegiatan,
        string $jam,
        string $tanggal,
        bool $isOrtu = false
    ): string {
        $closing = self::randomClosing($nama, $isOrtu);
        if ($isOrtu) {
            return "📋 *Notifikasi Kehadiran Kegiatan*\n\n" .
                "Halo, Orang Tua/Wali dari *{$nama}*,\n\n" .
                "Anak Anda telah hadir dalam kegiatan sekolah.\n\n" .
                "🎯 Kegiatan : *{$namaKegiatan}*\n" .
                "👤 Siswa   : {$nama}\n" .
                "📅 Tanggal  : {$tanggal}\n" .
                "🕐 Jam Masuk: {$jam}\n\n" .
                "{$closing}";
        }

        return "📋 *Notifikasi Kehadiran Kegiatan*\n\n" .
            "Halo, *{$nama}*,\n\n" .
            "Kehadiran Anda dalam kegiatan telah tercatat.\n\n" .
            "🎯 Kegiatan : *{$namaKegiatan}*\n" .
            "📅 Tanggal  : {$tanggal}\n" .
            "🕐 Jam Masuk: {$jam}\n\n" .
            "{$closing}";
    }

    /**
     * Laporan presensi per kegiatan untuk Wali Kelas
     */
    public static function kegiatanReportWaliKelas(
        string $namaKegiatan,
        string $namaKelas,
        string $namaWali,
        int $hadir,
        int $izin,
        int $sakit,
        int $alpha,
        array $listAbsen = []
    ): string {
        $msg = "🎯 *Laporan Presensi Kegiatan: {$namaKegiatan}*\n";
        $msg .= "🏫 Kelas: {$namaKelas}\n";
        $msg .= "👤 Wali Kelas: {$namaWali}\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= "---------------------------\n";
        $msg .= "✅ Hadir: {$hadir}\n";
        if ($izin > 0) $msg .= "📝 Izin: {$izin}\n";
        if ($sakit > 0) $msg .= "🤒 Sakit: {$sakit}\n";
        if ($alpha > 0) $msg .= "❌ Belum/Tidak Absen: {$alpha}\n";
        $msg .= "---------------------------\n";

        if (!empty($listAbsen)) {
            $msg .= "*Detail Siswa Non-Hadir:*\n";
            foreach ($listAbsen as $item) {
                $msg .= "- {$item}\n";
            }
            $msg .= "\n";
        } else {
            $msg .= "🎉 *Semua Siswa Terdaftar Hadir*\n\n";
        }

        $msg .= "_Generated by System_";
        return $msg;
    }

    /**
     * Laporan presensi per kegiatan Global (Seluruh Kelas)
     */
    public static function kegiatanReportGlobal(
        string $namaKegiatan,
        int $totalHadir,
        int $totalIzin,
        int $totalSakit,
        int $totalAlpha,
        array $statsByKelas = []
    ): string {
        $msg = "🎯 *Laporan Presensi Kegiatan*\n";
        $msg .= "📋 Kegiatan: *{$namaKegiatan}*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Total Hadir: {$totalHadir}\n";
        if ($totalIzin > 0) $msg .= "📝 Total Izin: {$totalIzin}\n";
        if ($totalSakit > 0) $msg .= "🤒 Total Sakit: {$totalSakit}\n";
        if ($totalAlpha > 0) $msg .= "❌ Belum/Tidak Absen: {$totalAlpha}\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        if (!empty($statsByKelas)) {
            $msg .= "*Rincian Per Kelas:*\n";
            foreach ($statsByKelas as $kelas => $st) {
                $msg .= "• *{$kelas}*: {$st['H']} Hadir";
                if (!empty($st['I'])) $msg .= ", {$st['I']} Izin";
                if (!empty($st['S'])) $msg .= ", {$st['S']} Sakit";
                if (!empty($st['A'])) $msg .= ", {$st['A']} Belum Absen";
                $msg .= "\n";
            }
            $msg .= "\n";
        }

        $msg .= "Generated by System\n\n";
        return $msg;
    }

    /**
     * Notifikasi Pengajuan Izin/Sakit Baru ke Wali Kelas
     */
    public static function leaveSubmittedToTeacher(
        string $namaWali,
        string $namaSiswa,
        string $kelas,
        string $jenis,
        string $tglMulai,
        string $tglSelesai,
        string $keterangan,
        string $code
    ): string {
        $msg = "📩 *PENGAJUAN " . strtoupper($jenis) . " SISWA*\n\n";
        $msg .= "Yth. Bapak/Ibu Wali Kelas *{$namaWali}*,\n\n";
        $msg .= "Terdapat permohonan *" . ucfirst($jenis) . "* baru dari siswa Anda:\n";
        $msg .= "👤 Nama Siswa : *{$namaSiswa}*\n";
        $msg .= "🏫 Kelas      : {$kelas}\n";
        $msg .= "📋 Jenis      : *" . ucfirst($jenis) . "*\n";
        $msg .= "📅 Rentang    : {$tglMulai}" . ($tglMulai !== $tglSelesai ? " s/d {$tglSelesai}" : "") . "\n";
        $msg .= "📝 Alasan     : {$keterangan}\n";
        $msg .= "🔖 Nomor Izin  : `{$code}`\n\n";
        $msg .= "Silakan login ke Web Admin / Dashboard untuk meninjau dan menyetujui pengajuan ini.\n\n";
        $msg .= "_Pesan otomatis Sistem Absensi_";
        return $msg;
    }

    /**
     * Notifikasi Pengajuan Izin/Sakit Disetujui ke Orang Tua / Siswa
     */
    public static function leaveApprovedToParent(
        string $namaSiswa,
        string $jenis,
        string $tglMulai,
        string $tglSelesai,
        string $approverName
    ): string {
        $msg = "✅ *PENGAJUAN " . strtoupper($jenis) . " DISETUJUI*\n\n";
        $msg .= "Yth. Orang Tua / Wali dari *{$namaSiswa}*,\n\n";
        $msg .= "Permohonan *" . ucfirst($jenis) . "* telah *DISETUJUI* oleh pihak sekolah.\n\n";
        $msg .= "👤 Nama Siswa : *{$namaSiswa}*\n";
        $msg .= "📋 Keterangan : *" . ucfirst($jenis) . "*\n";
        $msg .= "📅 Tanggal    : {$tglMulai}" . ($tglMulai !== $tglSelesai ? " s/d {$tglSelesai}" : "") . "\n";
        $msg .= "✍️ Disetujui  : {$approverName}\n\n";
        $msg .= "Status absensi siswa telah otomatis diperbarui pada sistem sekolah.\n\n";
        $msg .= "_Terima kasih atas kerja sama Anda._ 🙏";
        return $msg;
    }

    /**
     * Notifikasi Pengajuan Izin/Sakit Ditolak ke Orang Tua / Siswa
     */
    public static function leaveRejectedToParent(
        string $namaSiswa,
        string $jenis,
        string $tglMulai,
        string $tglSelesai,
        string $reason
    ): string {
        $msg = "⚠️ *PEMBERITAHUAN PENGAJUAN " . strtoupper($jenis) . "*\n\n";
        $msg .= "Yth. Orang Tua / Wali dari *{$namaSiswa}*,\n\n";
        $msg .= "Mohon maaf, permohonan *" . ucfirst($jenis) . "* untuk tanggal {$tglMulai}" . ($tglMulai !== $tglSelesai ? " s/d {$tglSelesai}" : "") . " *BELUM DAPAT DISETUJUI*.\n\n";
        $msg .= "📌 *Alasan:* {$reason}\n\n";
        $msg .= "Silakan hubungi pihak sekolah atau Wali Kelas untuk informasi lebih lanjut.\n\n";
        $msg .= "_Terima kasih._ 🙏";
        return $msg;
    }
}
