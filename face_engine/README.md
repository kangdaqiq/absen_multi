# Jagat Absen - Face Recognition Engine (Microservice)

Microservice pengenalan wajah mandiri (self-hosted) berbasis **Python FastAPI** & **InsightFace (ArcFace)** tanpa biaya langganan cloud.

## 🚀 Instalasi & Menjalankan di Server (Ubuntu / Debian)

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
# Jalankan langsung di background atau terminal:
uvicorn main:app --host 127.0.0.1 --port 8000

# Atau gunakan script:
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
ExecStart=/var/www/web/face_engine/venv/bin/uvicorn main:app --host 127.0.0.1 --port 8000 --workers 1
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
1. `GET /health`: Health check status service, threshold info, & quality filter settings.
2. `POST /extract-face-vector`: Ekstraksi 512-dimensi vektor embedding dari foto master dengan validasi ketajaman (Blur Detection) & orientasi wajah (Pose Filtering).
3. `POST /compare-face`: Membandingkan master embedding dengan foto selfie absen (threshold: 68% / 0.68) dengan validasi blur & pose.
