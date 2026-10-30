<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Rekapitulasi Presensi Mapel</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: center;
        }

        th {
            background-color: #f2f4f7;
            font-weight: bold;
            font-size: 10.5px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h2,
        .header h3 {
            margin: 0;
            padding: 0;
        }

        .meta-table {
            width: 100%;
            border: none;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .meta-table td {
            border: none;
            padding: 2px 4px;
            text-align: left;
            font-size: 11px;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .bg-total {
            background-color: #eaecf0;
            font-weight: bold;
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
            <img src="{{ $kopPath }}" style="width: 100%; max-height: 110px; object-fit: contain; margin-bottom: 8px;">
        @else
            <h2 style="font-size: 16px; text-transform: uppercase;">{{ $schoolName }}</h2>
            <p style="margin: 0; font-size: 10px; color: #555;">{{ $schoolAddress }}</p>
            <hr style="border: 1px double #000; margin-top: 8px; margin-bottom: 8px;">
        @endif

        <h3 style="margin-top: 10px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
            Laporan Rekapitulasi Presensi Siswa Per Mata Pelajaran
        </h3>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 14%;"><strong>Periode</strong></td>
            <td style="width: 36%;">: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
            <td style="width: 15%;"><strong>Kelas</strong></td>
            <td style="width: 35%;">: {{ $selectedKelas ? $selectedKelas->nama_kelas : 'Semua Kelas' }}</td>
        </tr>
        <tr>
            <td><strong>Mata Pelajaran</strong></td>
            <td>: {{ $selectedMapel ? $selectedMapel->nama_mapel : 'Semua Mata Pelajaran' }}</td>
            <td><strong>Guru Pengampu</strong></td>
            <td>: {{ $selectedGuru ? $selectedGuru->nama : 'Semua Guru' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">NIS</th>
                <th style="width: 26%;">Nama Siswa</th>
                <th style="width: 12%;">Kelas</th>
                <th style="width: 6%;">H</th>
                <th style="width: 6%;">T</th>
                <th style="width: 6%;">S</th>
                <th style="width: 6%;">I</th>
                <th style="width: 6%;">D</th>
                <th style="width: 6%;">A</th>
                <th style="width: 6%;">Total</th>
                <th style="width: 6%;">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totH = 0;
                $totT = 0;
                $totS = 0;
                $totI = 0;
                $totD = 0;
                $totA = 0;
                $totMeetings = 0;
            @endphp
            @forelse($allSiswa as $index => $siswa)
                @php
                    $sum = $summary[$siswa->id] ?? [
                        'H' => 0, 'T' => 0, 'S' => 0, 'I' => 0, 'D' => 0, 'A' => 0,
                        'total' => 0, 'persentase' => 0
                    ];
                    $totH += $sum['H'];
                    $totT += $sum['T'];
                    $totS += $sum['S'];
                    $totI += $sum['I'];
                    $totD += $sum['D'];
                    $totA += $sum['A'];
                    $totMeetings += $sum['total'];
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $siswa->nis ?? '-' }}</td>
                    <td class="text-left" style="padding-left: 8px;">{{ $siswa->nama }}</td>
                    <td>{{ $siswa->kelas?->nama_kelas ?? '-' }}</td>
                    <td>{{ $sum['H'] }}</td>
                    <td>{{ $sum['T'] }}</td>
                    <td>{{ $sum['S'] }}</td>
                    <td>{{ $sum['I'] }}</td>
                    <td>{{ $sum['D'] }}</td>
                    <td>{{ $sum['A'] }}</td>
                    <td class="fw-bold">{{ $sum['total'] }}</td>
                    <td class="fw-bold">{{ $sum['persentase'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="padding: 15px; color: #777;">Tidak ada data siswa ditemukan untuk kriteria filter ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($allSiswa) > 0)
            @php
                $overallPct = $totMeetings > 0 ? round((($totH + $totT) / $totMeetings) * 100, 1) : 0;
            @endphp
            <tfoot>
                <tr class="bg-total">
                    <td colspan="4" class="text-center">TOTAL KESELURUHAN</td>
                    <td>{{ $totH }}</td>
                    <td>{{ $totT }}</td>
                    <td>{{ $totS }}</td>
                    <td>{{ $totI }}</td>
                    <td>{{ $totD }}</td>
                    <td>{{ $totA }}</td>
                    <td>{{ $totMeetings }}</td>
                    <td>{{ $overallPct }}%</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div style="margin-top: 15px; font-size: 9.5px; color: #555;">
        <em>Keterangan: H = Hadir, T = Telat, S = Sakit, I = Izin, D = Dispensasi, A = Alpha / Tanpa Keterangan</em>
    </div>

    <div style="margin-top: 25px; text-align: right; font-size: 11px; padding-right: 30px;">
        {{ $signatureLocation }}, {{ now()->isoFormat('DD MMMM Y') }}
    </div>

    <table style="width: 100%; margin-top: 15px; border: none;">
        <tr>
            <td style="width: 50%; text-align: center; border: none; vertical-align: top; padding: 0 20px;">
                <div style="font-size: 11px;">Dibuat Oleh,</div>
                <div style="font-size: 11px; font-weight: bold;">
                    @if($selectedGuru)
                        Guru Pengampu Mata Pelajaran
                    @else
                        Waka Kurikulum
                    @endif
                </div>
                <br><br><br><br>
                <div style="font-size: 11px; font-weight: bold; text-decoration: underline;">
                    {{ $selectedGuru ? $selectedGuru->nama : ($namaWaka ?: '..........................................') }}
                </div>
                @if($selectedGuru && !empty($selectedGuru->nip))
                    <div style="font-size: 10px;">NIP. {{ $selectedGuru->nip }}</div>
                @elseif(!$selectedGuru && $nipWaka)
                    <div style="font-size: 10px;">NIP. {{ $nipWaka }}</div>
                @endif
            </td>
            <td style="width: 50%; text-align: center; border: none; vertical-align: top; padding: 0 20px;">
                <div style="font-size: 11px;">Mengetahui,</div>
                <div style="font-size: 11px; font-weight: bold;">Kepala Sekolah</div>
                <br><br><br><br>
                <div style="font-size: 11px; font-weight: bold; text-decoration: underline;">
                    {{ $namaKepsek ?: '..........................................' }}
                </div>
                @if($nipKepsek)
                    <div style="font-size: 10px;">NIP. {{ $nipKepsek }}</div>
                @endif
            </td>
        </tr>
    </table>
</body>

</html>
