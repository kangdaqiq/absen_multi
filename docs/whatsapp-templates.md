# WhatsApp Message Templates

Semua template notifikasi WhatsApp disimpan di satu file untuk kemudahan maintenance.

## 📁 Lokasi File

`app/Services/WhatsAppMessageTemplates.php`

## 🎯 Cara Penggunaan

### 1. Import Class

```php
use App\Services\WhatsAppMessageTemplates;
```

### 2. Gunakan Template

#### Check-In Notification
```php
$message = WhatsAppMessageTemplates::checkIn(
    nama: 'Ahmad',
    jamMasuk: '07:15',
    kelas: 'X RPL 1',
    status: 'Hadir'
);
```

#### Check-Out Notification
```php
$message = WhatsAppMessageTemplates::checkOut(
    nama: 'Ahmad',
    jamMasuk: '07:15',
    jamPulang: '15:30',
    hours: 8,
    minutes: 15,
    authorizedBy: 'Pak Budi'
);
```

#### Late Check-In
```php
$message = WhatsAppMessageTemplates::checkInLate(
    nama: 'Ahmad',
    jamMasuk: '07:45',
    kelas: 'X RPL 1',
    lateMinutes: 30
);
```

#### Alpha Notification (Student)
```php
$message = WhatsAppMessageTemplates::alphaStudent(nama: 'Ahmad');
```

#### Alpha Notification (Parent)
```php
$message = WhatsAppMessageTemplates::alphaParent(
    nama: 'Ahmad',
    kelas: 'X RPL 1'
);
```

#### Daily Report (Class Group)
```php
$message = WhatsAppMessageTemplates::dailyReportClass(
    namaKelas: 'X RPL 1',
    masuk: 28,
    tidakMasuk: 2,
    absentByStatus: [
        'A' => ['Ahmad', 'Budi'],
        'I' => [],
        'S' => [],
        'B' => []
    ]
);
```

#### Daily Report (Wali Kelas)
```php
$message = WhatsAppMessageTemplates::dailyReportWaliKelas(
    namaKelas: 'X RPL 1',
    namaWali: 'Pak Doni',
    masuk: 28,
    tidakMasuk: 2,
    listAbsen: ['Ahmad (Alpha)', 'Budi (Sakit)']
);
```

#### Global Daily Report
```php
$message = WhatsAppMessageTemplates::dailyReportGlobal(
    totalMasuk: 150,
    totalTidakMasuk: 10,
    absentByStatus: [
        'A' => ['Ahmad (X RPL 1)', 'Budi (X RPL 2)'],
        'I' => [],
        'S' => ['Citra (X RPL 1)'],
        'B' => []
    ]
);
```

#### Final Absence Report
```php
$message = WhatsAppMessageTemplates::finalAbsenceReport(
    totalAbsent: 10,
    absentStudentsGrouped: $grouped // Collection grouped by status
);
```

#### Abnormal Attendance Alert
```php
$message = WhatsAppMessageTemplates::abnormalAttendanceAlert(
    nama: 'Ahmad',
    kelas: 'X RPL 1',
    alphaCount: 5,
    bolosCount: 3,
    totalDays: 20,
    periodStart: '01/01/2026',
    periodEnd: '20/01/2026'
);
```

#### Teacher Schedule
```php
$message = WhatsAppMessageTemplates::teacherSchedule(
    namaGuru: 'Pak Budi',
    jadwalHariIni: [
        [
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
            'mata_pelajaran' => 'Matematika',
            'kelas' => 'X RPL 1'
        ],
        // ...
    ]
);
```

#### Broadcast Message
```php
$message = WhatsAppMessageTemplates::broadcast(
    title: 'Pengumuman Libur',
    message: 'Besok libur nasional, tidak ada kegiatan belajar mengajar.',
    footer: 'Pengumuman dari Kepala Sekolah' // optional
);
```

## ✅ Keuntungan

1. **Centralized** - Semua template di satu tempat
2. **Easy to Maintain** - Ubah template cukup di satu file
3. **Consistent** - Format pesan konsisten di seluruh aplikasi
4. **Type Safe** - Parameter jelas dan terdokumentasi
5. **Reusable** - Bisa digunakan di berbagai controller/command

## 📝 Menambah Template Baru

Tambahkan method static baru di class `WhatsAppMessageTemplates`:

```php
public static function namaTemplate(string $param1, string $param2): string
{
    return "Template message dengan {$param1} dan {$param2}";
}
```

## 🔄 Migration dari Kode Lama

Ganti hardcoded message dengan template:

**Sebelum**:
```php
$message = "✅ *Notifikasi Absen Masuk*\n\n" .
    "Assalamualaikum, *{$nama}*,\n\n" .
    "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
    // ... dst
```

**Sesudah**:
```php
$message = WhatsAppMessageTemplates::checkIn($nama, $jamMasuk, $kelas);
```
