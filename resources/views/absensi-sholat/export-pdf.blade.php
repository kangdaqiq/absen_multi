<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Sholat</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }

        .header h3 {
            margin: 6px 0 0 0;
            font-size: 14px;
            font-weight: normal;
        }

        .meta {
            margin-bottom: 15px;
            font-size: 11px;
            line-height: 1.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            font-size: 10px;
        }

        th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        td.text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-tepat {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-terlambat {
            background-color: #fef3c7;
            color: #92400e;
        }

        .footer {
            margin-top: 30px;
            float: right;
            text-align: center;
            width: 220px;
            font-size: 11px;
        }

        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="header">
        @php
            $hasKop = !empty($kopSurat);
            $kopPath = null;
            if ($hasKop) {
                if (\Illuminate\Support\Str::startsWith($kopSurat, 'schools/')) {
                    $kopPath = storage_path('app/public/' . $kopSurat);
                } else {
                    $kopPath = public_path('img/' . $kopSurat);
                }
            } else {
                $kopPath = public_path('img/default_kop.png');
            }
        @endphp

        @if($kopPath && file_exists($kopPath))
            <img src="{{ $kopPath }}" style="width: 100%; max-height: 100px; object-fit: contain; margin-bottom: 8px;">
        @else
            <h2>{{ $schoolName }}</h2>
            <hr style="border: 0.5px solid #000; margin: 8px 0;">
        @endif

        <h3 style="font-weight: bold;">LAPORAN REKAPITULASI PRESENSI SHOLAT</h3>
    </div>

    <div class="meta">
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}<br>
        <strong>Dicetak Pada:</strong> {{ now()->isoFormat('D MMMM Y, HH:mm') }} WIB<br>
        <strong>Total Kehadiran:</strong> {{ $attendances->count() }} Data
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 65px;">Tanggal</th>
                <th>Nama</th>
                <th style="width: 45px;">Peran</th>
                <th style="width: 75px;">Kelas / NIP</th>
                <th style="width: 65px;">Sholat</th>
                <th style="width: 50px;">Jam Absen</th>
                <th style="width: 60px;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $idx => $att)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($att->tanggal)->format('d/m/Y') }}</td>
                    <td><strong>{{ $att->person_name }}</strong></td>
                    <td class="text-center">{{ $att->person_type === 'guru' ? 'Guru' : 'Siswa' }}</td>
                    <td class="text-center">{{ $att->student->kelas->nama_kelas ?? ($att->guru->nip ?? '-') }}</td>
                    <td class="text-center" style="font-weight: bold; text-transform: uppercase;">{{ $att->sholat }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($att->jam_absen)->format('H:i') }}</td>
                    <td class="text-center">
                        <span class="badge {{ in_array($att->status, ['hadir', 'tepat_waktu', 'jamaah']) ? 'badge-tepat' : 'badge-terlambat' }}">
                            {{ in_array($att->status, ['hadir', 'tepat_waktu', 'jamaah']) ? 'Hadir' : 'Terlambat' }}
                        </span>
                    </td>
                    <td>{{ $att->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #6b7280;">Tidak ada rekaman presensi sholat pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>Mengetahui,</div>
        <div>Kepala Sekolah / Koordinator</div>
        <div class="signature-space"></div>
        <div><strong>( .................................................. )</strong></div>
    </div>
</body>
</html>
