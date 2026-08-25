<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Guru</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .stats {
            margin: 10px 0;
            padding: 8px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
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
            <h2 style="margin: 0 0 5px 0;">{{ $schoolName ?? 'Sistem Absensi' }}</h2>
            <p style="margin: 0 0 5px 0;">{{ $schoolAddress ?? 'Laporan Kehadiran Guru & Staff' }}</p>
            <hr>
        @endif
        <h3 style="margin: 5px 0;">REKAPITULASI ABSENSI GURU & STAFF</h3>
        <p style="margin: 0;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <div class="stats">
        <strong>Ringkasan:</strong>
        Total: <b>{{ $stats['total'] }}</b> |
        Hadir (Tepat Waktu): <b>{{ $stats['hadir'] }}</b> |
        Terlambat: <b>{{ $stats['terlambat'] ?? 0 }}</b> |
        Tidak Hadir / Alpha: <b>{{ $stats['tidak_hadir'] }}</b>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="10%">Tanggal</th>
                <th width="20%">Nama Guru / Staff</th>
                <th width="12%">NIP</th>
                <th width="14%">Shift</th>
                <th width="10%">Status</th>
                <th width="8%">Jam Masuk</th>
                <th width="8%">Jam Pulang</th>
                <th width="14%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($absensi as $a)
                <tr>
                    <td align="center">{{ $loop->iteration }}</td>
                    <td align="center">{{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }}</td>
                    <td><b>{{ $a->guru->nama ?? '-' }}</b></td>
                    <td align="center">{{ $a->guru->nip ?? '-' }}</td>
                    <td align="center">{{ $a->shift ? $a->shift->nama_shift : '-' }}</td>
                    <td align="center">
                        @if($a->status == 'Hadir')
                            <span class="badge-success">Hadir</span>
                        @elseif($a->status == 'Terlambat')
                            <span class="badge-warning">Terlambat ({{ $a->menit_terlambat }}m)</span>
                        @elseif(in_array($a->status, ['Izin', 'Sakit']))
                            <span class="badge-warning">{{ $a->status }}</span>
                        @else
                            <span class="badge-danger">{{ $a->status }}</span>
                        @endif
                    </td>
                    <td align="center">{{ $a->jam_masuk ? \Carbon\Carbon::parse($a->jam_masuk)->format('H:i') : '-' }}</td>
                    <td align="center">{{ $a->jam_pulang ? \Carbon\Carbon::parse($a->jam_pulang)->format('H:i') : '-' }}</td>
                    <td>{{ $a->keterangan ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 25px; font-size: 9px; color: #666;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>

</html>