<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $subscription->invoice_number ?? 'INV-' . $subscription->id }} - {{ $school->name ?? 'Langganan Sekolah' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #eef2ff;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --dark: #0f172a;
            --gray-700: #334155;
            --gray-500: #64748b;
            --gray-300: #cbd5e1;
            --gray-100: #f1f5f9;
            --gray-50: #f8fafc;
            --white: #ffffff;
            --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            --card-shadow-lg: 0 25px 50px -12px rgba(79, 70, 229, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            color: var(--gray-700);
            min-height: 100vh;
            padding: 24px 16px 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 880px;
        }

        /* Top Bar Branding */
        .brand-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 0 8px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--dark);
            text-decoration: none;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--gray-300);
            background: var(--white);
            color: var(--gray-700);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-action:hover {
            background: var(--gray-50);
            border-color: var(--gray-500);
        }

        /* Main Invoice Card */
        .invoice-card {
            background: var(--white);
            border-radius: 24px;
            box-shadow: var(--card-shadow-lg);
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        /* Header Card */
        .invoice-head {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            color: var(--white);
            padding: 36px 36px 32px;
            position: relative;
            overflow: hidden;
        }

        .invoice-head::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(129, 140, 248, 0.2) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .head-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .invoice-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .invoice-sub {
            color: #c7d2fe;
            font-size: 0.95rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .status-unpaid {
            background: rgba(245, 158, 11, 0.2);
            color: #fde68a;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }

        .status-paid {
            background: rgba(16, 185, 129, 0.2);
            color: #a7f3d0;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #fecaca;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .head-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .meta-item .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #a5b4fc;
            margin-bottom: 4px;
        }

        .meta-item .val {
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Body Section */
        .invoice-body {
            padding: 36px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-bottom: 32px;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .invoice-head, .invoice-body {
                padding: 24px 20px;
            }
        }

        .bill-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-300);
            border-radius: 16px;
            padding: 20px;
        }

        .bill-card h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            margin-bottom: 12px;
        }

        .bill-card .school-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .bill-card p {
            font-size: 0.875rem;
            color: var(--gray-700);
            line-height: 1.5;
        }

        /* Items Table */
        .table-wrap {
            border: 1px solid var(--gray-300);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: var(--gray-50);
            padding: 14px 18px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            font-weight: 700;
            border-bottom: 1px solid var(--gray-300);
        }

        td {
            padding: 16px 18px;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--gray-100);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .item-desc {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        /* Summary Box */
        .summary-box {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 32px;
        }

        .summary-list {
            width: 100%;
            max-width: 380px;
            background: var(--gray-50);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--gray-300);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .summary-row.total {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px dashed var(--gray-300);
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
        }

        /* QRIS Payment Showcase */
        .qris-section {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border: 2px solid #c7d2fe;
            border-radius: 20px;
            padding: 28px;
            text-align: center;
            margin-bottom: 32px;
            position: relative;
        }

        .qris-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .qris-box {
            background: white;
            padding: 16px;
            border-radius: 16px;
            display: inline-block;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.2);
            margin: 12px 0 16px;
            border: 2px solid #e0e7ff;
        }

        .qris-box img {
            display: block;
            max-width: 100%;
            height: auto;
            width: 240px;
            border-radius: 8px;
        }

        .nominal-highlight {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            padding: 12px 20px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            margin: 10px 0 16px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nominal-highlight:hover {
            border-color: var(--primary);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }

        .copy-tag {
            font-size: 0.75rem;
            background: var(--primary-light);
            color: var(--primary);
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
        }

        .pulse-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-700);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background: var(--warning);
            border-radius: 50%;
            animation: pulse-animation 1.5s infinite;
        }

        @keyframes pulse-animation {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }

        /* Paid Success Box */
        .paid-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #6ee7b7;
            border-radius: 20px;
            padding: 36px 24px;
            text-align: center;
            margin-bottom: 32px;
        }

        .paid-icon {
            width: 64px;
            height: 64px;
            background: var(--success);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 16px;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }

        /* Payment Steps Accordion */
        .instructions-card {
            border: 1px solid var(--gray-300);
            border-radius: 16px;
            padding: 20px;
            background: var(--gray-50);
        }

        .instructions-card h4 {
            font-size: 0.95rem;
            color: var(--dark);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instructions-card ol {
            padding-left: 20px;
            font-size: 0.85rem;
            color: var(--gray-700);
            line-height: 1.7;
        }

        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .brand-header, .qris-section, .instructions-card, .footer {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Brand & Print Bar -->
    <div class="brand-header">
        <div class="brand-logo">
            <img src="{{ asset('images/logo/logo.svg') }}" alt="JagatTech" style="height: 36px; width: auto;">
            <span style="border-left: 2px solid var(--gray-300); padding-left: 12px; margin-left: 4px; font-size: 1.05rem; font-weight: 700; color: var(--gray-700);">Invoice</span>
        </div>
        <div class="action-btns">
            <button onclick="window.print()" class="btn-action">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Main Invoice Card -->
    <div class="invoice-card">
        <!-- Head -->
        <div class="invoice-head">
            <div class="head-top">
                <div>
                    <h1 class="invoice-title">INVOICE PEMBAYARAN</h1>
                    <p class="invoice-sub">Perpanjangan Langganan Sistem Absensi</p>
                </div>
                <div>
                    @if($subscription->status === 'paid')
                        <span class="status-badge status-paid">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Lunas / Terbayar
                        </span>
                    @elseif($subscription->status === 'cancelled')
                        <span class="status-badge status-cancelled">Dibatalkan</span>
                    @else
                        <span class="status-badge status-unpaid">
                            <span class="pulse-dot" style="background: #fde68a;"></span>
                            Menunggu Pembayaran
                        </span>
                    @endif
                </div>
            </div>

            <div class="head-meta">
                <div class="meta-item">
                    <div class="label">No. Invoice</div>
                    <div class="val">{{ $subscription->invoice_number ?? 'INV-' . str_pad($subscription->id, 5, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="meta-item">
                    <div class="label">Tanggal Dibuat</div>
                    <div class="val">{{ $subscription->created_at ? $subscription->created_at->translatedFormat('d F Y H:i') : now()->translatedFormat('d F Y') }}</div>
                </div>
                <div class="meta-item">
                    <div class="label">Periode Langganan</div>
                    <div class="val">{{ $subscription->billing_cycle === 'yearly' ? '1 Tahun (Tahunan)' : '1 Bulan (Bulanan)' }}</div>
                </div>
                <div class="meta-item">
                    <div class="label">Masa Aktif Baru</div>
                    <div class="val">{{ $subscription->expired_at ? $subscription->expired_at->translatedFormat('d F Y') : '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="invoice-body">
            <!-- School Details & Provider -->
            <div class="grid-2">
                <div class="bill-card">
                    <h3>Ditujukan Kepada</h3>
                    <div class="school-name">{{ $school->name ?? 'Sekolah Pelanggan' }}</div>
                    <p>
                        <strong>Kode Sekolah:</strong> {{ $school->code ?? '-' }}<br>
                        <strong>Alamat:</strong> {{ $school->address ?? 'Alamat Sekolah' }}<br>
                        <strong>No. Telepon / WA:</strong> {{ $school->operator_phone ?: ($school->phone ?: '-') }}
                    </p>
                </div>
                <div class="bill-card">
                    <h3>Penyedia Layanan</h3>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <img src="{{ asset('images/logo/logo.svg') }}" alt="Jagat Tech" style="height: 24px; width: auto;">
                    </div>
                    <div class="school-name" style="font-size: 1.1rem; color: var(--dark);">Jagat Tech</div>
                    <p>
                        <strong>Alamat:</strong> Jl. Murnijaya RT 03 RW 04, Tumijajar, Tulang Bawang Barat, Lampung, 34594<br>
                        <strong>No. Telp / WA:</strong> 081524824563<br>
                        <strong>Email Support:</strong> admin@jagattech.my.id
                    </p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Deskripsi Paket</th>
                            <th style="text-align: center;">Durasi</th>
                            <th style="text-align: right;">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="item-name">{{ $package->name ?? 'Paket Langganan' }}</div>
                                @php
                                    $studentLimit = $package?->student_limit ?? ($school->student_limit ?? 0);
                                    $teacherLimit = $package?->teacher_limit ?? ($school->teacher_limit ?? 0);
                                    $studentText = $studentLimit > 0 ? 'Maks ' . number_format($studentLimit, 0, ',', '.') . ' Siswa' : 'Unlimited Siswa';
                                    $teacherText = $teacherLimit > 0 ? number_format($teacherLimit, 0, ',', '.') . ' Guru & Staf' : 'Unlimited Guru & Staf';
                                @endphp
                                <div class="item-desc">
                                    Kuota: {{ $studentText }}, {{ $teacherText }}, WhatsApp Gateway & Telegram Bot Otomatis
                                </div>
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                {{ $subscription->billing_cycle === 'yearly' ? '12 Bulan' : '1 Bulan' }}
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                Rp {{ number_format($baseAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Box -->
            <div class="summary-box">
                <div class="summary-list">
                    <div class="summary-row">
                        <span>Subtotal Biaya:</span>
                        <span>Rp {{ number_format($baseAmount, 0, ',', '.') }}</span>
                    </div>
                    @if($uniqueCode > 0)
                    <div class="summary-row" style="color: var(--primary);">
                        <span>Kode Unik Verifikasi:</span>
                        <span>+ Rp {{ number_format($uniqueCode, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="summary-row total">
                        <span>Total Pembayaran:</span>
                        <span>Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- QRIS Payment / Paid State -->
            @if($subscription->status === 'paid')
                <div class="paid-box">
                    <div class="paid-icon">✓</div>
                    <h2 style="color: #065f46; font-size: 1.5rem; margin-bottom: 8px;">PEMBAYARAN LUNAS</h2>
                    <p style="color: #047857; font-size: 0.95rem; margin-bottom: 4px;">
                        Terima kasih! Pembayaran Anda telah kami terima pada <strong>{{ $subscription->paid_at ? $subscription->paid_at->translatedFormat('d F Y H:i') : '' }}</strong>.
                    </p>
                    <p style="color: #047857; font-size: 0.9rem;">
                        Masa aktif sistem sekolah Anda telah otomatis diperpanjang hingga <strong>{{ $subscription->expired_at ? $subscription->expired_at->translatedFormat('d F Y') : '' }}</strong>.
                    </p>
                </div>
            @elseif($subscription->status === 'unpaid' && !empty($qrisImage))
                <div class="qris-section" id="qrisSection">
                    <span class="qris-badge">Pembayaran Instan Otomatis</span>
                    <h2 style="font-size: 1.35rem; color: var(--dark); margin-bottom: 4px;">Scan QRIS</h2>
                    <p style="font-size: 0.875rem; color: var(--gray-500);">
                        Dapat dibayar menggunakan BCA, Mandiri, BRI, BNI, Dana, GoPay, OVO, ShopeePay, LinkAja, atau seluruh aplikasi perbankan berlogo QRIS.
                    </p>

                    <div class="qris-box">
                        <img src="{{ $qrisImage }}" alt="QRIS Pembayaran">
                    </div>

                    <div>
                        <div class="nominal-highlight" onclick="copyAmount()" title="Klik untuk salin nominal">
                            <span id="amountDisplay">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                            <span class="copy-tag">📋 Salin Nominal</span>
                        </div>
                    </div>

                    <div class="pulse-indicator">
                        <span class="pulse-dot"></span>
                        <span id="statusText">Sistem mendeteksi pembayaran realtime secara otomatis...</span>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="instructions-card">
                    <h4>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        Petunjuk Pembayaran QRIS
                    </h4>
                    <ol>
                        <li>Buka aplikasi Mobile Banking (BCA mobile, Livin Mandiri, BRImo, BNI Mobile) atau E-Wallet (Dana, GoPay, OVO, ShopeePay).</li>
                        <li>Pilih menu <strong>Bayar / QRIS</strong>, lalu scan barcode QRIS di atas.</li>
                        <li>Pastikan nominal pembayaran sesuai persis dengan <strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong> (termasuk 3 digit kode unik).</li>
                        <li>Selesaikan transaksi. Sistem akan langsung memverifikasi otomatis dalam 5-10 detik dan mengaktifkan perpanjangan langganan tanpa perlu konfirmasi manual.</li>
                    </ol>
                </div>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; {{ date('Y') }} <strong>JagatTech</strong> &bull; Sistem Informasi Absensi Sekolah. Dokumen tagihan ini sah dan diterbitkan secara elektronik.</p>
    </div>
</div>

<script>
    function copyAmount() {
        const amount = "{{ (int)$totalAmount }}";
        navigator.clipboard.writeText(amount).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Nominal Disalin!',
                text: 'Nominal Rp ' + parseInt(amount).toLocaleString('id-ID') + ' berhasil disalin ke clipboard.',
                timer: 1800,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
    }

    // Real-time Status Polling
    @if($subscription->status === 'unpaid')
    const token = "{{ $subscription->invoice_token }}";
    let isPolling = true;

    function checkInvoiceStatus() {
        if (!isPolling) return;

        fetch(`/invoice/${token}/status`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.is_paid) {
                    isPolling = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Pembayaran Diterima!',
                        text: 'Terima kasih, langganan sekolah telah aktif kembali.',
                        confirmButtonText: 'Segarkan Halaman',
                        confirmButtonColor: '#4f46e5'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            })
            .catch(err => console.log('Polling status error:', err));
    }

    // Poll every 4 seconds
    setInterval(checkInvoiceStatus, 4000);
    @endif
</script>

</body>
</html>
