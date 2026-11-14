<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Akumulasi Poin Pelanggaran Siswa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header h2 {
            margin: 0 0 4px 0;
            font-size: 16px;
            text-transform: uppercase;
        }

        .header h3 {
            margin: 0 0 4px 0;
            font-size: 13px;
            font-weight: normal;
        }

        .header p {
            margin: 0;
            font-size: 10px;
            color: #666;
        }

        .meta-info {
            margin-bottom: 12px;
            font-size: 11px;
        }

        .meta-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-info td {
            padding: 2px 0;
            border: none;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table.data-table th, table.data-table td {
            border: 1px solid #999;
            padding: 5px 6px;
            text-align: left;
            font-size: 10px;
        }

        table.data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-table td {
            border: none;
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }

        .signature-space {
            height: 50px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $schoolName }}</h2>
        <h3>LAPORAN REKAPITULASI AKUMULASI POIN PELANGGARAN SISWA</h3>
        <p>{{ $schoolAddress }}</p>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td style="width: 15%;"><strong>Periode:</strong></td>
                <td style="width: 35%;">
                    @if($startDate && $endDate)
                        {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                    @else
                        Seluruh Riwayat (Kumulatif)
                    @endif
                </td>
                <td style="width: 18%;"><strong>Ringkasan:</strong></td>
                <td style="width: 32%;">{{ $totalSiswa }} Siswa • {{ $totalKasus }} Kasus ({{ $totalPoin }} Poin)</td>
            </tr>
            <tr>
                <td><strong>Dicetak Pada:</strong></td>
                <td>{{ now()->translatedFormat('d F Y H:i') }} WIB</td>
                <td><strong>Status Siswa:</strong></td>
                <td>{{ $filterPoin === 'with_points' ? 'Hanya Siswa Berpoin' : 'Semua Siswa' }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 65px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 75px;">Kelas</th>
                <th style="width: 60px;">Total Kasus</th>
                <th style="width: 60px;">Akumulasi Poin</th>
                <th>Pelanggaran Terakhir</th>
                <th>Tindakan / SP Terakhir</th>
                <th style="width: 60px;">Jml Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @php $totalPoinCount = 0; $totalKasusCount = 0; @endphp
            @forelse($items as $idx => $s)
                @php 
                    $totalPoinCount += (int)$s->total_poin;
                    $totalKasusCount += (int)$s->total_kasus;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center font-mono">{{ $s->nis ?? '-' }}</td>
                    <td><strong>{{ $s->nama }}</strong></td>
                    <td class="text-center">{{ $s->kelas?->nama_kelas ?? '-' }}</td>
                    <td class="text-center">{{ $s->total_kasus }}</td>
                    <td class="text-center font-bold" style="{{ (int)$s->total_poin > 25 ? 'color: #dc2626;' : '' }}">
                        {{ (int)$s->total_poin }}
                    </td>
                    <td>
                        @if($s->latestPelanggaran)
                            {{ $s->latestPelanggaran->judul_pelanggaran }}
                            <div style="font-size: 8.5px; color: #666;">({{ $s->latestPelanggaran->tanggal?->format('d/m/Y') }} • +{{ $s->latestPelanggaran->poin }} poin)</div>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($s->latestPenanganan)
                            <strong>{{ $s->latestPenanganan->tindakan_sp_label }}</strong>
                            <div style="font-size: 8.5px; color: #666;">({{ $s->latestPenanganan->tanggal?->format('d/m/Y') }})</div>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td class="text-center">{{ (int)$s->total_penanganan }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px; color: #888;">
                        Tidak ada data pelanggaran siswa yang memenuhi filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($items->isNotEmpty())
            <tfoot>
                <tr style="background-color: #f9fafb; font-weight: bold;">
                    <td colspan="4" class="text-right">TOTAL:</td>
                    <td class="text-center">{{ $totalKasusCount }}</td>
                    <td class="text-center">{{ $totalPoinCount }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <table class="signature-table">
        <tr>
            <td></td>
            <td>
                <div>Mengetahui,</div>
                <div>Guru BK / Kesiswaan</div>
                <div class="signature-space"></div>
                <div><strong>( ..................................................... )</strong></div>
            </td>
        </tr>
    </table>
</body>
</html>
