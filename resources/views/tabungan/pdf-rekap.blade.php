<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Tabungan Siswa</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1E40AF;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0;
            color: #1E40AF;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0 0;
            font-size: 10px;
            color: #666;
        }
        .meta {
            margin-bottom: 12px;
            font-size: 10px;
        }
        .meta table {
            width: 100%;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data th, table.data td {
            border: 1px solid #ddd;
            padding: 5px 7px;
        }
        table.data th {
            background-color: #1E40AF;
            color: #fff;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-row {
            background-color: #F8FAFC;
            font-weight: bold;
        }
        .footer {
            margin-top: 25px;
            width: 100%;
        }
        .footer td {
            vertical-align: top;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $school->nama_sekolah ?? 'SEKOLAH' }}</h2>
        <p>{{ $school->alamat ?? 'Laporan Resmi Tabungan Siswa' }}</p>
        <h3 style="margin: 6px 0 0; font-size: 13px;">REKAPITULASI SALDO TABUNGAN SISWA</h3>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td style="width: 50%;">
                    <strong>Filter Kelas:</strong> {{ $selectedKelas ? $selectedKelas->nama_kelas : 'Semua Kelas' }}<br>
                    <strong>Total Siswa Terdata:</strong> {{ $students->count() }} Siswa
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Tanggal Cetak:</strong> {{ now()->format('d/m/Y H:i') }} WIB<br>
                    <strong>Dicetak Oleh:</strong> {{ $printedBy }}
                </td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 75px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 70px;">Kelas</th>
                <th class="text-right" style="width: 80px;">Total Setor</th>
                <th class="text-right" style="width: 80px;">Total Tarik</th>
                <th class="text-center" style="width: 45px;">Trx</th>
                <th class="text-right" style="width: 95px;">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandSetor = 0;
                $grandTarik = 0;
                $grandSaldo = 0;
            @endphp
            @forelse($students as $idx => $s)
                @php
                    $grandSetor += (int) ($s->total_setor ?? 0);
                    $grandTarik += (int) ($s->total_tarik ?? 0);
                    $grandSaldo += (int) $s->saldo_tabungan;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $s->nis ?? '-' }}</td>
                    <td class="font-bold">{{ $s->nama }}</td>
                    <td>{{ $s->kelas?->nama_kelas ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format((int) ($s->total_setor ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((int) ($s->total_tarik ?? 0), 0, ',', '.') }}</td>
                    <td class="text-center">{{ (int) ($s->total_transaksi ?? 0) }}</td>
                    <td class="text-right font-bold">Rp {{ number_format((int) $s->saldo_tabungan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data siswa untuk kriteria ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-center">TOTAL KESELURUHAN</td>
                <td class="text-right">Rp {{ number_format($grandSetor, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($grandTarik, 0, ',', '.') }}</td>
                <td class="text-center">-</td>
                <td class="text-right font-bold">Rp {{ number_format($grandSaldo, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="footer">
        <tr>
            <td style="width: 70%;"></td>
            <td style="width: 30%; text-align: center;">
                Mengetahui,<br>
                Petugas / Pengelola Tabungan<br><br><br><br>
                <strong>( {{ $printedBy }} )</strong>
            </td>
        </tr>
    </table>
</body>
</html>
