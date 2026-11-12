<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Pelanggaran Siswa</title>
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

        .badge-ringan { background: #dcfce7; color: #15803d; }
        .badge-sedang { background: #fef9c3; color: #854d0e; }
        .badge-berat  { background: #fee2e2; color: #b91c1c; }

        .footer {
            margin-top: 25px;
            width: 100%;
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
        <h3>LAPORAN REKAPITULASI PELANGGARAN SISWA</h3>
        <p>{{ $schoolAddress }}</p>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td style="width: 15%;"><strong>Periode:</strong></td>
                <td style="width: 35%;">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
                <td style="width: 18%;"><strong>Total Pelanggaran:</strong></td>
                <td style="width: 32%;">{{ $totalKasus }} Kasus ({{ $totalPoin }} Poin)</td>
            </tr>
            <tr>
                <td><strong>Dicetak Pada:</strong></td>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
                <td><strong>Waktu:</strong></td>
                <td>WIB</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 65px;">Kode</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 60px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 65px;">Kelas</th>
                <th>Pelanggaran</th>
                <th style="width: 55px;">Kategori</th>
                <th style="width: 35px;">Poin</th>
                <th style="width: 55px;">Status</th>
                <th>Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            @php $totalPoinCount = 0; @endphp
            @forelse($items as $idx => $p)
                @php $totalPoinCount += (int)$p->poin; @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center font-mono" style="font-size: 9px;">{{ $p->code }}</td>
                    <td class="text-center">{{ $p->tanggal?->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $p->student?->nis ?? '-' }}</td>
                    <td><strong>{{ $p->student?->nama ?? '-' }}</strong></td>
                    <td class="text-center">{{ $p->student?->kelas?->nama_kelas ?? '-' }}</td>
                    <td>
                        {{ $p->judul_pelanggaran }}
                        @if($p->keterangan)
                            <div style="font-size: 8.5px; color: #555;">{{ $p->keterangan }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $p->kategori }}">{{ ucfirst($p->kategori) }}</span>
                    </td>
                    <td class="text-center font-bold">{{ $p->poin }}</td>
                    <td class="text-center">{{ ucfirst($p->status) }}</td>
                    <td style="font-size: 9px;">{{ $p->tindak_lanjut ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 15px; color: #888;">
                        Tidak ada data catatan pelanggaran pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($items->isNotEmpty())
            <tfoot>
                <tr style="background-color: #f9fafb; font-weight: bold;">
                    <td colspan="8" class="text-right">TOTAL POIN PELANGGARAN:</td>
                    <td class="text-center">{{ $totalPoinCount }}</td>
                    <td colspan="2"></td>
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

