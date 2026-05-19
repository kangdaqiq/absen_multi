<!DOCTYPE html>
<html>

<head>
    <title>Laporan Absensi</title>
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

        .footer {
            margin-top: 30px;
            text-align: right;
        }

        .signature {
            margin-top: 50px;
            text-align: right;
            padding-right: 30px;
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
            <img src="{{ $kopPath }}" style="width: 100%; max-height: 120px; object-fit: contain; margin-bottom: 10px;">
        @else
            <h2>{{ $schoolName }}</h2>
            <p style="margin: 0; font-size: 10px;">{{ $schoolAddress }}</p>
            <hr style="border: 1px double #000; margin-top: 10px;">
        @endif

        <h3 style="margin-top: 15px;">Laporan Rekapitulasi Absensi Siswa</h3>
    </div>

    <div class="meta">
        <strong>Kelas:</strong> {{ $kelas ? $kelas->nama_kelas : 'Semua Kelas' }}<br>
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d
        {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Nama Siswa</th>
                <th width="8%">Hadir</th>
                <th width="12%">Tidak Hadir</th>
                <th width="8%">Telat</th>
                <th width="8%">Izin</th>
                <th width="8%">Sakit</th>
                <th width="8%">Alpha</th>
                <th width="8%">Bolos</th>
                <th width="15%">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekap as $r)
                @php
                    $tidakHadir = $r['izin'] + $r['sakit'] + $r['alpha'] + $r['bolos'];
                    $total = $r['hadir'] + $r['telat'] + $tidakHadir;
                    $persentase = $total > 0 ? round((($r['hadir'] + $r['telat']) / $total) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td style="text-align: left; padding-left: 10px;">{{ $r['nama'] }}</td>
                    <td>{{ $r['hadir'] }}</td>
                    <td>{{ $tidakHadir }}</td>
                    <td>{{ $r['telat'] }}</td>
                    <td>{{ $r['izin'] }}</td>
                    <td>{{ $r['sakit'] }}</td>
                    <td>{{ $r['alpha'] }}</td>
                    <td>{{ $r['bolos'] }}</td>
                    <td>{{ $persentase }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php
                $totHadir = collect($rekap)->sum('hadir');
                $totTelat = collect($rekap)->sum('telat');
                $totTidakHadir = collect($rekap)->sum('izin') + collect($rekap)->sum('sakit') + collect($rekap)->sum('alpha') + collect($rekap)->sum('bolos');
                $totAll = $totHadir + $totTelat + $totTidakHadir;
                $totPersen = $totAll > 0 ? round((($totHadir + $totTelat) / $totAll) * 100, 1) : 0;
            @endphp
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="2">TOTAL</td>
                <td>{{ $totHadir }}</td>
                <td>{{ $totTidakHadir }}</td>
                <td>{{ $totTelat }}</td>
                <td>{{ collect($rekap)->sum('izin') }}</td>
                <td>{{ collect($rekap)->sum('sakit') }}</td>
                <td>{{ collect($rekap)->sum('alpha') }}</td>
                <td>{{ collect($rekap)->sum('bolos') }}</td>
                <td>{{ $totPersen }}%</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 12px; padding-right: 40px;">
        {{ $signatureLocation }}, {{ now()->isoFormat('DD MMMM Y') }}
    </div>

    <table style="width: 100%; margin-top: 30px; border: none;">
        <tr>
            <td style="width: 50%; text-align: center; border: none; vertical-align: top; padding: 0 20px;">
                <div style="font-size: 12px;">Diketahui Oleh,</div>
                <div style="font-size: 12px; font-weight: bold;">Kepala Sekolah</div>
                <br><br><br><br>
                <div
                    style="font-size: 12px; display: inline-block; padding-top: 4px; min-width: 180px;">
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
                <div
                    style="font-size: 12px; display: inline-block; padding-top: 4px; min-width: 180px;">
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