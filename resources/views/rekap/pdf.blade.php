<!DOCTYPE html>
<html>
<head>
    <title>Laporan Absensi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2, .header h3 { margin: 0; }
        .meta { margin-bottom: 10px; }
        .footer { margin-top: 30px; text-align: right; }
        .signature { margin-top: 50px; text-align: right; padding-right: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $schoolName }}</h2>
        <p style="margin: 0; font-size: 10px;">{{ $schoolAddress }}</p>
        <hr style="border: 1px double #000; margin-top: 10px;">
        <h3 style="margin-top: 15px;">Laporan Rekapitulasi Absensi Siswa</h3>
    </div>

    <div class="meta">
        <strong>Kelas:</strong> {{ $kelas ? $kelas->nama_kelas : 'Semua Kelas' }}<br>
        <strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="35%">Nama Siswa</th>
                <th width="10%">Hadir</th>
                <th width="10%">Izin</th>
                <th width="10%">Sakit</th>
                <th width="10%">Alpha</th>
                <th width="10%">Bolos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekap as $r)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="text-align: left; padding-left: 10px;">{{ $r['nama'] }}</td>
                <td>{{ $r['hadir'] }}</td>
                <td>{{ $r['izin'] }}</td>
                <td>{{ $r['sakit'] }}</td>
                <td>{{ $r['alpha'] }}</td>
                <td>{{ $r['bolos'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>{{ $signatureLocation }}, {{ now()->isoFormat('D MMMM Y') }}</div>
        <div class="signature">
            <br><br><br>
            _______________________<br>
            Wali Kelas / Kepala Sekolah
        </div>
    </div>
</body>
</html>
