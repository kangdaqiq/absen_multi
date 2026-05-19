<!DOCTYPE html>
<html>

<head>
    <title>Laporan Absensi Kelas</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2,
        .header h3 {
            margin: 0;
        }

        .meta {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        @php
            $kopPath = null;
            if (!empty($kopSurat)) {
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
            <img src="{{ $kopPath }}" style="width: 100%; max-height: 120px; object-fit: contain; margin-bottom: 10px;">
        @else
            <h2>{{ $schoolName ?: 'Rekapitulasi' }}</h2>
            <p style="margin: 0; font-size: 10px;">{{ $schoolAddress }}</p>
            <hr style="border: 1px double #000; margin-top: 10px;">
        @endif

        <h3 style="margin-top: 15px;">Laporan Rekapitulasi Kehadiran per Kelas</h3>
    </div>

    <div class="meta">
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d
        {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Nama Kelas</th>
                <th width="8%">Hadir</th>
                <th width="12%">Tidak Hadir</th>
                <th width="8%">Telat</th>
                <th width="8%">Izin</th>
                <th width="8%">Sakit</th>
                <th width="8%">Alpha</th>
                <th width="8%">Bolos</th>
                <th width="15%">Persentase (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allKelas as $index => $k)
                @php 
                    $stat = $summary[$k->id] ?? ['H'=>0,'T'=>0,'I'=>0,'S'=>0,'B'=>0,'A'=>0];
                    $tidakHadir = $stat['I'] + $stat['S'] + $stat['B'] + $stat['A'];
                    $total = $stat['H'] + $stat['T'] + $tidakHadir;
                    $persentase = $total > 0 ? round((($stat['H'] + $stat['T']) / $total) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="text-align: left; padding-left: 10px;">{{ $k->nama_kelas }}</td>
                    <td>{{ $stat['H'] }}</td>
                    <td>{{ $tidakHadir }}</td>
                    <td>{{ $stat['T'] }}</td>
                    <td>{{ $stat['I'] }}</td>
                    <td>{{ $stat['S'] }}</td>
                    <td>{{ $stat['A'] }}</td>
                    <td>{{ $stat['B'] }}</td>
                    <td>{{ $persentase }}%</td>
                </tr>
            @endforeach
        </tbody>
        @php
            $totH = 0; $totT = 0; $totI = 0; $totS = 0; $totB = 0; $totA = 0;
            foreach($allKelas as $k) {
                $s = $summary[$k->id] ?? ['H'=>0,'T'=>0,'I'=>0,'S'=>0,'B'=>0,'A'=>0];
                $totH += $s['H']; $totT += $s['T']; $totI += $s['I'];
                $totS += $s['S']; $totB += $s['B']; $totA += $s['A'];
            }
            $totTidakHadir = $totI + $totS + $totB + $totA;
            $totGlobal = $totH + $totT + $totTidakHadir;
            $totPersen = $totGlobal > 0 ? round((($totH + $totT) / $totGlobal) * 100, 1) : 0;
        @endphp
        <tfoot>
            <tr style="font-weight: bold; background-color: #dbeafe;">
                <td colspan="2" style="text-align: right; padding-right: 8px;">TOTAL KEHADIRAN</td>
                <td>{{ $totH }}</td>
                <td>{{ $totTidakHadir }}</td>
                <td>{{ $totT }}</td>
                <td>{{ $totI }}</td>
                <td>{{ $totS }}</td>
                <td>{{ $totA }}</td>
                <td>{{ $totB }}</td>
                <td style="color: #1d4ed8;">{{ $totPersen }}%</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 12px; padding-right: 40px;">
        {{ $signatureLocation ? $signatureLocation . ',' : '' }} {{ now()->isoFormat('DD MMMM Y') }}
    </div>

    <table style="width: 100%; margin-top: 30px; border: none;">
        <tr>
            <td style="width: 50%; text-align: center; border: none; vertical-align: top; padding: 0 20px;">
                <div style="font-size: 12px;">Diketahui Oleh,</div>
                <div style="font-size: 12px; font-weight: bold;">Kepala Sekolah</div>
                <br><br><br><br>
                <div style="font-size: 12px; display: inline-block; padding-top: 4px; min-width: 180px;">
                    {{ $namaKepsek }}
                </div>
                @if($nipKepsek)
                    <div style="font-size: 11px;">NIP. {{ $nipKepsek }}</div>
                @endif
            </td>
            <td style="width: 50%; text-align: center; border: none; vertical-align: top; padding: 0 20px;">
                <div style="font-size: 12px;">Dibuat Oleh,</div>
                <div style="font-size: 12px; font-weight: bold;">Waka Kesiswaan</div>
                <br><br><br><br>
                <div style="font-size: 12px; display: inline-block; padding-top: 4px; min-width: 180px;">
                    {{ $namaWaka }}
                </div>
                @if($nipWaka)
                    <div style="font-size: 11px;">NIP. {{ $nipWaka }}</div>
                @endif
            </td>
        </tr>
    </table>

</body>

</html>
