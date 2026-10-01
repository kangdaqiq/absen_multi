<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Guru & Staff</title>
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
        Total Guru/Staff: <b>{{ $stats['total_guru'] ?? $allGurus->count() }}</b> |
        Hadir (Tepat Waktu): <b>{{ $stats['hadir'] ?? 0 }}</b> |
        Terlambat: <b>{{ $stats['terlambat'] ?? 0 }}</b> |
        Izin: <b>{{ $stats['izin'] ?? 0 }}</b> |
        Sakit: <b>{{ $stats['sakit'] ?? 0 }}</b> |
        Tidak Hadir / Alpha: <b>{{ $stats['tidak_hadir'] ?? 0 }}</b>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="24%">Nama Guru / Staff</th>
                <th width="12%">NIP</th>
                <th width="12%">Shift</th>
                <th width="7%">Hadir</th>
                <th width="7%">Terlambat</th>
                <th width="8%">Tidak Hadir</th>
                <th width="6%">Izin</th>
                <th width="6%">Sakit</th>
                <th width="6%">Alpha</th>
                <th width="8%">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allGurus as $g)
                @php
                    $s = $summary[$g->id] ?? [
                        'hadir' => 0,
                        'terlambat' => 0,
                        'tidak_hadir' => 0,
                        'izin' => 0,
                        'sakit' => 0,
                        'alpha' => 0,
                        'total' => 0,
                        'persen' => 0,
                    ];
                @endphp
                <tr>
                    <td align="center">{{ $loop->iteration }}</td>
                    <td><b>{{ $g->nama }}</b></td>
                    <td align="center">{{ $g->nip ?? '-' }}</td>
                    <td align="center">{{ $g->defaultShift ? $g->defaultShift->nama_shift : '-' }}</td>
                    <td align="center">{{ $s['hadir'] }}</td>
                    <td align="center">{{ $s['terlambat'] }}</td>
                    <td align="center">{{ $s['tidak_hadir'] }}</td>
                    <td align="center">{{ $s['izin'] }}</td>
                    <td align="center">{{ $s['sakit'] }}</td>
                    <td align="center">{{ $s['alpha'] }}</td>
                    <td align="center">{{ $s['persen'] }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php
                $totHadir = collect($summary)->sum('hadir');
                $totTerlambat = collect($summary)->sum('terlambat');
                $totTidakHadir = collect($summary)->sum('tidak_hadir');
                $totIzin = collect($summary)->sum('izin');
                $totSakit = collect($summary)->sum('sakit');
                $totAlpha = collect($summary)->sum('alpha');
                $grandTotal = $totHadir + $totTerlambat + $totTidakHadir;
                $grandPersen = $grandTotal > 0 ? round((($totHadir + $totTerlambat) / $grandTotal) * 100, 1) : 0;
            @endphp
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="4" align="center">TOTAL</td>
                <td align="center">{{ $totHadir }}</td>
                <td align="center">{{ $totTerlambat }}</td>
                <td align="center">{{ $totTidakHadir }}</td>
                <td align="center">{{ $totIzin }}</td>
                <td align="center">{{ $totSakit }}</td>
                <td align="center">{{ $totAlpha }}</td>
                <td align="center">{{ $grandPersen }}%</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 25px; font-size: 9px; color: #666;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>