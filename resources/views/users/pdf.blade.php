<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Akun Pengguna & Kredensial Login</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h2 {
            margin: 0 0 4px 0;
            font-size: 16px;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 0 0 6px 0;
            font-size: 10px;
            color: #475569;
        }

        .header-divider {
            border: none;
            border-top: 2px solid #0f172a;
            border-bottom: 1px solid #94a3b8;
            height: 3px;
            margin: 6px 0 14px 0;
        }

        .doc-title {
            text-align: center;
            margin-bottom: 12px;
        }

        .doc-title h3 {
            margin: 0 0 3px 0;
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .doc-title p {
            margin: 0;
            font-size: 9px;
            color: #64748b;
        }

        .meta-box {
            width: 100%;
            margin-bottom: 12px;
            padding: 6px 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 9.5px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 4px;
            border: none;
            vertical-align: top;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: middle;
        }

        table.data-table th {
            background-color: #059669;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
        }

        .badge-role {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
            text-align: center;
        }

        .role-admin {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .role-waka {
            background-color: #f3e8ff;
            color: #6b21a8;
        }

        .role-walas {
            background-color: #ffedd5;
            color: #9a3412;
        }

        .role-guru {
            background-color: #d1fae5;
            color: #065f46;
        }

        .role-super {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .role-default {
            background-color: #f1f5f9;
            color: #334155;
        }

        .notice-box {
            margin-top: 15px;
            padding: 8px 10px;
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 3px solid #f59e0b;
            border-radius: 3px;
            font-size: 8.5px;
            color: #92400e;
        }

        .notice-box strong {
            color: #78350f;
        }

        .footer-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }

        .footer-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .signature-box {
            width: 200px;
            text-align: center;
            float: right;
            font-size: 9.5px;
        }

        .signature-space {
            height: 55px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    {{-- Header Kop Surat --}}
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
            <img src="{{ $kopPath }}" style="width: 100%; max-height: 95px; object-fit: contain; margin-bottom: 6px;">
        @else
            <h2>{{ $schoolName ?? 'SISTEM ABSENSI SEKOLAH' }}</h2>
            @if(!empty($schoolAddress))
                <p>{{ $schoolAddress }}</p>
            @endif
            <div class="header-divider"></div>
        @endif
    </div>

    {{-- Judul Dokumen --}}
    <div class="doc-title">
        <h3>DAFTAR AKUN PENGGUNA & KREDENSIAL LOGIN</h3>
        <p>Kategori: {{ $roleTitle ?? 'Semua Pengguna' }} | Tanggal Cetak: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB</p>
    </div>

    {{-- Ringkasan Metadata --}}
    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td width="15%"><strong>Sekolah / Instansi</strong></td>
                <td width="35%">: {{ $schoolName ?? '-' }}</td>
                <td width="15%"><strong>Total Akun</strong></td>
                <td width="35%">: <b>{{ $users->count() }} Akun</b></td>
            </tr>
            <tr>
                <td><strong>Kategori Role</strong></td>
                <td>: {{ $roleTitle ?? 'Semua Pengguna' }}</td>
                <td><strong>Status Dokumen</strong></td>
                <td>: <span style="color: #b91c1c; font-weight: bold;">RAHASIA & TERBATAS</span></td>
            </tr>
        </table>
    </div>

    {{-- Tabel Utama Data Pengguna --}}
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="27%">Nama Lengkap</th>
                <th width="18%">Username</th>
                <th width="17%">Password</th>
                <th width="15%">Role Sistem</th>
                <th width="18%">Email / NIP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $index => $u)
                @php
                    $roleClass = match($u->role) {
                        'admin' => 'role-admin',
                        'waka_kurikulum' => 'role-waka',
                        'wali_kelas' => 'role-walas',
                        'guru', 'teacher' => 'role-guru',
                        'super_admin' => 'role-super',
                        default => 'role-default'
                    };
                    $nip = $u->guru?->nip ?: null;
                @endphp
                <tr>
                    <td align="center">{{ $index + 1 }}</td>
                    <td>
                        <b>{{ $u->full_name }}</b>
                        @if($nip)
                            <div style="font-size: 8.5px; color: #64748b;">NIP: {{ $nip }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="font-mono" style="font-weight: bold; color: #047857;">{{ $u->username }}</span>
                    </td>
                    <td>
                        <span class="font-mono" style="font-weight: bold; color: #b45309; background-color: #fef3c7; padding: 1px 4px; border-radius: 2px;">
                            {{ $u->display_password }}
                        </span>
                    </td>
                    <td align="center">
                        <span class="badge-role {{ $roleClass }}">
                            {{ $u->role_label }}
                        </span>
                    </td>
                    <td style="font-size: 8.5px; color: #475569;">
                        {{ $u->email ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" align="center" style="padding: 15px; color: #64748b;">
                        Tidak ada data pengguna yang ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Peringatan Keamanan Kredensial --}}
    <div class="notice-box">
        <strong>PENTING & RAHASIA:</strong> Dokumen ini memuat data login (username & password) untuk akses sistem absensi. Harap segera distribusikan kepada yang bersangkutan secara pribadi dan simpan lembaran ini di tempat yang aman. Disarankan kepada setiap pengguna untuk memperbarui kata sandi secara mandiri setelah login pertama kali.
    </div>

    {{-- Blok Tanda Tangan --}}
    <table class="footer-table">
        <tr>
            <td width="60%">
                <div style="font-size: 8.5px; color: #64748b; margin-top: 15px;">
                    Dicetak otomatis oleh Sistem Absensi Jagat pada: {{ now()->format('d/m/Y H:i:s') }}<br>
                    ID Pengunduh: {{ auth()->user()?->full_name ?? 'Administrator' }} ({{ auth()->user()?->username ?? '-' }})
                </div>
            </td>
            <td width="40%">
                <div class="signature-box">
                    <div>{{ !empty($signatureLocation) ? $signatureLocation : 'Tempat' }}, {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</div>
                    <div style="margin-top: 2px;">Administrator Sistem</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ auth()->user()?->full_name ?? 'Administrator' }}</div>
                    <div style="font-size: 8.5px; color: #64748b;">NIP: {{ auth()->user()?->guru?->nip ?? '-' }}</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
