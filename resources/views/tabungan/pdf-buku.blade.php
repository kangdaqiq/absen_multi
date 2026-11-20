<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Tabungan - {{ $student->nama }}</title>
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
            margin-bottom: 12px;
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
        .passbook-box {
            border: 1px solid #1E40AF;
            background-color: #F8FAFC;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .passbook-box table {
            width: 100%;
        }
        .passbook-box td {
            font-size: 11px;
            padding: 2px 0;
        }
        .saldo-tag {
            font-size: 16px;
            font-weight: bold;
            color: #047857;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data th, table.data td {
            border: 1px solid #ddd;
            padding: 5px 6px;
        }
        table.data th {
            background-color: #1E40AF;
            color: #fff;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
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
        <p>{{ $school->alamat ?? 'Layanan Pengelolaan Tabungan Siswa' }}</p>
        <h3 style="margin: 6px 0 0; font-size: 13px; color: #1E40AF;">BUKU TABUNGAN SISWA</h3>
    </div>

    <div class="passbook-box">
        <table>
            <tr>
                <td style="width: 18%;"><strong>Nama Siswa</strong></td>
                <td style="width: 35%;">: <strong>{{ $student->nama }}</strong></td>
                <td style="width: 20%;"><strong>Total Setoran</strong></td>
                <td style="width: 27%;">: Rp {{ number_format($totalSetor, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>NIS / NISN</strong></td>
                <td>: {{ $student->nis ?? '-' }} / {{ $student->nisn ?? '-' }}</td>
                <td><strong>Total Tarikan</strong></td>
                <td>: Rp {{ number_format($totalTarik, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Kelas</strong></td>
                <td>: {{ $student->kelas?->nama_kelas ?? '-' }}</td>
                <td><strong>SALDO SAAT INI</strong></td>
                <td>: <span class="saldo-tag">Rp {{ number_format((int) $student->saldo_tabungan, 0, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 75px;">No. Ref</th>
                <th>Keterangan / Transaksi</th>
                <th class="text-right" style="width: 75px;">Debit (Setor)</th>
                <th class="text-right" style="width: 75px;">Kredit (Tarik)</th>
                <th class="text-right" style="width: 85px;">Saldo Akhir</th>
                <th class="text-center" style="width: 60px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutasi as $idx => $m)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $m->tanggal ? $m->tanggal->format('d/m/Y') : '-' }}</td>
                    <td style="font-family: monospace;">{{ $m->code }}</td>
                    <td>{{ $m->keterangan ?: ($m->jenis === 'setor' ? 'Setoran Tabungan' : 'Penarikan Tabungan') }}</td>
                    <td class="text-right" style="color: #047857;">
                        {{ $m->jenis === 'setor' ? 'Rp ' . number_format((int) $m->nominal, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right" style="color: #DC2626;">
                        {{ $m->jenis === 'tarik' ? 'Rp ' . number_format((int) $m->nominal, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-bold">
                        Rp {{ number_format((int) $m->saldo_sesudah, 0, ',', '.') }}
                    </td>
                    <td class="text-center" style="font-size: 9px;">
                        {{ $m->recorder?->full_name ?? 'Admin' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada catatan transaksi di buku tabungan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td style="width: 50%; text-align: center;">
                Pemilik Rekening / Siswa,<br><br><br><br>
                <strong>( {{ $student->nama }} )</strong>
            </td>
            <td style="width: 50%; text-align: center;">
                Dicetak pada: {{ now()->format('d/m/Y H:i') }}<br>
                Petugas Tabungan / Kasir,<br><br><br><br>
                <strong>( {{ $printedBy }} )</strong>
            </td>
        </tr>
    </table>
</body>
</html>
