<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lisensi Expired — Sistem Absensi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #2d1b00 50%, #3d2000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 480px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }
        .icon { font-size: 64px; margin-bottom: 24px; display: block; }
        h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 12px; color: #fbbf24; }
        p { font-size: 0.95rem; color: rgba(255,255,255,0.7); line-height: 1.7; margin-bottom: 16px; }
        .expiry-box {
            background: rgba(251,191,36,0.1);
            border: 1px solid rgba(251,191,36,0.3);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .expiry-box .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #fcd34d; margin-bottom: 4px; }
        .expiry-box .date { font-size: 1.3rem; font-weight: 700; color: #fbbf24; }
        .contact-box {
            background: rgba(99,102,241,0.15);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .contact-box p { margin: 0; color: rgba(255,255,255,0.9); }
        .contact-box .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #a5b4fc; margin-bottom: 6px; }
        .badge {
            display: inline-block;
            background: rgba(251,191,36,0.15);
            border: 1px solid rgba(251,191,36,0.4);
            color: #fbbf24;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">⏰</span>
        <span class="badge">Lisensi Kadaluarsa</span>
        <h1>Masa Berlaku Habis</h1>
        <p>Lisensi aplikasi ini telah melewati masa berlakunya. Perpanjang lisensi untuk melanjutkan penggunaan.</p>

        @if(isset($licenseExpiredAt))
            <div class="expiry-box">
                <div class="label">Expired Pada</div>
                <div class="date">{{ $licenseExpiredAt }}</div>
            </div>
        @endif

        <div class="contact-box">
            <p class="label">Perpanjang Lisensi</p>
            <p>Hubungi <strong>KangDaQiQ</strong> untuk perpanjangan lisensi dan melanjutkan akses ke sistem.</p>
        </div>

        <p style="font-size:0.8rem; color: rgba(255,255,255,0.3);">
            Sistem Absensi — Self Hosted Edition<br>
            Error: LICENSE_EXPIRED
        </p>
    </div>
</body>
</html>
