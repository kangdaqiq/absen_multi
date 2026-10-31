# Jagat Absen - Face Recognition Engine (Microservice v1.3.0)

Microservice pengenalan wajah mandiri (self-hosted) berbasis **Python FastAPI** & **InsightFace (ArcFace)** dengan optimasi khusus untuk **STB Armbian (Amlogic S905X)** maupun **VPS / Server Standar**.

## ✨ Fitur Utama (v1.3.0)
- **Auto Hardware-Profile (`FACE_PROFILE=auto`):** Otomatis mendeteksi arsitektur CPU (ARM64/x86_64) dan kapasitas RAM untuk mengaktifkan `LOW_SPEC_MODE` pada STB / Server RAM $\le 2.5\text{ GB}$.
- **Hemat RAM ~200 MB:** Hanya memuat modul `['detection', 'recognition']` (tanpa membebani RAM dengan model 3D/2D landmarks & gender/age), menonaktifkan ONNX Memory Arena pada perangkat low-spec, serta memanggil `malloc_trim(0)` berkala.
- **0% Idle CPU Spinning:** Mematikan *thread busy-spinning* pada ONNX Runtime (`allow_spinning=0`) sehingga suhu CPU STB tetap dingin saat tidak ada proses scan.
- **Non-Blocking FastAPI Event-Loop & Bounded Queue:** Menggunakan `ThreadPoolExecutor` terbatas dan `asyncio.Semaphore` sehingga hanya 1 gambar yang didekode di RAM pada satu waktu (anti-OOM saat jam sibuk masuk sekolah).
- **1:N NumPy Matrix Search + LRU Cache (`/identify-face`):** Pencarian ratusan/ribuan wajah dalam `< 0.5 ms` menggunakan perkalian matriks BLAS dan *in-memory matrix cache*.
- **CLAHE Auto-Lighting & Multi-Template Centroid:** Toleransi otomatis terhadap ruangan redup/backlight dan penggabungan beberapa sampel wajah ke dalam 1 vektor rata-rata (*Centroid*).

## 🚀 Instalasi & Menjalankan di Server (Ubuntu / Debian / Armbian)

### 1. Masuk ke direktori dan buat virtual environment:
```bash
cd /var/www/web/face_engine
python3 -m venv venv
source venv/bin/activate
```

### 2. Install dependensi:
```bash
pip install -r requirements.txt
```

> **Catatan Dependensi Sistem (jika diperlukan oleh OpenCV/ONNX):**
> ```bash
> apt-get update && apt-get install -y libgl1 libglib2.0-0
> ```

### 3. Jalankan service:
```bash
chmod +x run.sh
./run.sh
```

### 4. (Rekomendasi) Jalankan Otomatis via Systemd Service:
Buat file `/etc/systemd/system/face-engine.service`:
```ini
[Unit]
Description=Jagat Absen Face Recognition Engine
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/web/face_engine
Environment="MALLOC_ARENA_MAX=2"
Environment="FACE_PROFILE=auto"
Environment="FACE_MODEL_NAME=buffalo_l"
Environment="FACE_THRESHOLD=0.68"
ExecStart=/var/www/web/face_engine/venv/bin/uvicorn main:app --host 127.0.0.1 --port 8000 --workers 1 --limit-concurrency 16 --timeout-keep-alive 5
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```
Lalu aktifkan:
```bash
systemctl daemon-reload
systemctl enable --now face-engine
systemctl status face-engine
```

## 📡 Endpoints
1. `GET /health`: Health check status service, profil hardware (`low_spec_mode`), jumlah matriks ter-cache, & quality filter settings.
2. `POST /extract-face-vector`: Ekstraksi 512-dimensi vektor embedding (L2-normalized) dari foto master dengan CLAHE, Blur Detection, Pose Filtering, & Multi-Template Centroid.
3. `POST /compare-face`: Pencocokan 1:1 antara master embedding dengan foto selfie absen.
4. `POST /identify-face`: Pencarian 1:N berkecepatan tinggi berbasis matriks NumPy & LRU Matrix Cache.
