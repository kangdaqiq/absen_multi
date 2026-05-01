<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lisensi Tidak Valid — Sistem Absensi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
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
        .icon {
            font-size: 64px;
            margin-bottom: 24px;
            display: block;
            filter: drop-shadow(0 0 20px rgba(255,80,80,0.5));
        }
        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: #ff6b6b;
        }
        p {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.7);
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .license-key {
            background: rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 10px 16px;
            font-family: monospace;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            margin-bottom: 28px;
            word-break: break-all;
        }
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
            background: rgba(255,80,80,0.2);
            border: 1px solid rgba(255,80,80,0.4);
            color: #ff6b6b;
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">🔒</span>
        <span class="badge">Lisensi Tidak Valid</span>
        <h1>Akses Ditangguhkan</h1>
        <p>Aplikasi ini memerlukan lisensi yang valid untuk beroperasi. Lisensi yang terdaftar tidak dapat diverifikasi oleh server.</p>

        <form action="{{ route('license.update-key') }}" method="POST" style="margin-bottom: 24px;">
            @csrf
            <div style="position: relative;">
                <input type="text" name="license_key" placeholder="Masukkan License Key..." value="{{ config('app.license_key') }}" style="width: 100%; padding: 12px 16px; border-radius: 8px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: white; font-family: monospace; outline: none; transition: border 0.3s;" onfocus="this.style.borderColor='#6366f1';" onblur="this.style.borderColor='rgba(255,255,255,0.2)';">
                <button type="submit" style="position: absolute; right: 8px; top: 8px; padding: 6px 16px; background: #6366f1; border: none; border-radius: 6px; color: white; font-weight: bold; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#4f46e5';" onmouseout="this.style.background='#6366f1';">Simpan</button>
            </div>
            <p style="font-size: 0.8rem; margin-top: 8px; color: rgba(255,255,255,0.5); text-align: left;">Masukkan lisensi baru Anda di sini dan tekan Simpan.</p>
        </form>

        <div class="contact-box">
            <p class="label">Hubungi Provider</p>
            <p>Segera hubungi <strong>KangDaQiQ</strong> untuk verifikasi atau aktivasi ulang lisensi Anda.</p>
        </div>

        <p style="font-size:0.8rem; color: rgba(255,255,255,0.3);">
            Sistem Absensi — Self Hosted Edition<br>
            Error: LICENSE_INVALID
        </p>
    </div>
</body>
</html>
