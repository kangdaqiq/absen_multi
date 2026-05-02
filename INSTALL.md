# 📦 Panduan Instalasi — Sistem Absensi Self-Hosted

**Versi:** Sistem Absensi Multi-Tenant  
**Provider:** kangdaqiq / jagattech  
**Kontak:** https://wa.me/6281234567890

---

## ✅ Persyaratan Server

| Kebutuhan | Minimum | Rekomendasi |
|-----------|---------|-------------|
| OS | Ubuntu 20.04 | **Ubuntu 22.04 LTS** |
| RAM | 1 GB | 2 GB |
| Storage | 10 GB | 20 GB |
| PHP | 8.2 | **8.3** |
| MySQL | 8.0 | 8.0 |
| Web Server | Apache atau Nginx | Apache |
| Composer | 2.x | 2.x |

### PHP Extensions yang Dibutuhkan
```
pdo_mysql, mbstring, bcmath, gd, zip, intl, xml, curl, pcntl, opcache
```

---

## 🚀 Langkah Instalasi

### 1. Install Dependensi Sistem

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server supervisor curl unzip

# PHP 8.3
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-mysql php8.3-mbstring php8.3-bcmath \
  php8.3-gd php8.3-zip php8.3-intl php8.3-xml php8.3-curl php8.3-pcntl \
  php8.3-opcache libapache2-mod-php8.3

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Enable mod_rewrite
sudo a2enmod rewrite && sudo systemctl restart apache2
```

### 2. Setup Database

```bash
sudo mysql
```

```sql
CREATE DATABASE absen_sell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'absen_user'@'localhost' IDENTIFIED BY 'passwordKuat123';
GRANT ALL PRIVILEGES ON absen_sell.* TO 'absen_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Upload & Extract Aplikasi

```bash
# Buat folder aplikasi
sudo mkdir -p /var/www/absen

# Upload file release-client.zip ke server, lalu extract:
sudo unzip release-client.zip -d /tmp/
sudo cp -r /tmp/release-client/. /var/www/absen/
```

### 4. Install Dependencies PHP

```bash
cd /var/www/absen
sudo composer install --no-dev --optimize-autoloader
```

### 5. Konfigurasi .env

```bash
sudo cp .env.example .env
sudo nano .env
```

**Isi yang WAJIB diubah:**
```env
APP_KEY=                          # ← biarkan kosong dulu, diisi step selanjutnya
APP_URL=http://IP-SERVER-KAMU     # ← ganti dengan IP atau domain server
APP_MODE=self_hosted              # ← JANGAN DIUBAH

LICENSE_KEY=XXXX-XXXX-XXXX-XXXX  # ← dari provider
LICENSE_SERVER_URL=https://absen.kangdaqiq.com

DB_HOST=127.0.0.1
DB_DATABASE=absen_sell
DB_USERNAME=absen_user
DB_PASSWORD=passwordKuat123       # ← sesuaikan dengan password MySQL

WA_API_BASE_URL=http://localhost:3000
WA_API_USER=admin
WA_API_PASS=changeme
```

### 6. Generate APP_KEY & Setup Database

```bash
cd /var/www/absen
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

### 7. Atur Permissions

```bash
sudo chown -R www-data:www-data /var/www/absen
sudo chmod -R 755 /var/www/absen
sudo chmod -R 775 /var/www/absen/storage
sudo chmod -R 775 /var/www/absen/bootstrap/cache
```

### 8. Konfigurasi Apache

Buat file virtual host:
```bash
sudo nano /etc/apache2/sites-available/absen.conf
```

Isi dengan:
```apache
<VirtualHost *:80>
    ServerName IP-ATAU-DOMAIN-KAMU
    DocumentRoot /var/www/absen/public

    <Directory /var/www/absen/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/absen_error.log
    CustomLog ${APACHE_LOG_DIR}/absen_access.log combined
</VirtualHost>
```

Aktifkan:
```bash
sudo a2ensite absen.conf
sudo a2dissite 000-default.conf   # nonaktifkan default
sudo systemctl reload apache2
```

### 9. Setup Crontab (Laravel Scheduler)

```bash
sudo crontab -u www-data -e
```

Tambahkan baris ini:
```
* * * * * cd /var/www/absen && php artisan schedule:run >> /dev/null 2>&1
```

### 10. Setup Supervisor (Queue Worker)

```bash
sudo nano /etc/supervisor/conf.d/absen-queue.conf
```

Isi dengan:
```ini
[program:absen-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/absen/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/absen/storage/logs/queue.log
```

Aktifkan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start absen-queue:*
```

### 11. Install GOWA (WhatsApp API)

```bash
# Download binary GOWA
wget https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/gowa-linux-amd64
chmod +x gowa-linux-amd64
sudo mv gowa-linux-amd64 /usr/local/bin/gowa
```

Jalankan GOWA via Supervisor — tambahkan ke `/etc/supervisor/conf.d/absen-gowa.conf`:
```ini
[program:absen-gowa]
command=gowa rest --port=3000 --basic-auth=admin:changeme
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/absen/storage/logs/gowa.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

**Scan QR WhatsApp:**
Buka browser → `http://IP-SERVER:3000` → klik "Login" → scan QR dengan HP

---

## ✅ Verifikasi Instalasi

Buka browser dan akses: `http://IP-SERVER-KAMU`

Jika berhasil, akan muncul halaman login sistem absensi.

---

## 🔄 Update Aplikasi

Jika provider mengirim update:

```bash
cd /var/www/absen

# Backup dulu (opsional)
mysqldump -u absen_user -p absen_sell > backup_$(date +%Y%m%d).sql

# Extract update
sudo unzip -o release-client-vX.X.X.zip -d /tmp/
sudo rsync -av --exclude='.env' --exclude='storage/app/license_cache.json' \
  /tmp/release-client/. /var/www/absen/

# Jalankan update
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize

# Restart queue
sudo supervisorctl restart absen-queue:*
```

---

## 🆘 Troubleshooting

### Halaman 500 Error
```bash
cat /var/www/absen/storage/logs/laravel.log | tail -50
```

### Antrian WA tidak jalan
```bash
sudo supervisorctl status
sudo supervisorctl restart absen-queue:*
```

### License tidak valid
- Pastikan `LICENSE_KEY` sudah benar di `.env`
- Pastikan server bisa akses internet (ping `absen.kangdaqiq.com`)
- Hubungi provider untuk aktivasi

### Permission error
```bash
sudo chown -R www-data:www-data /var/www/absen/storage
sudo chmod -R 775 /var/www/absen/storage
```

---

## 📞 Kontak Provider

Hubungi provider jika mengalami kendala instalasi atau membutuhkan **LICENSE_KEY**:

- **WhatsApp:** https://wa.me/6281234567890
- **Email:** admin@kangdaqiq.com

---

_Dokumen ini adalah panduan instalasi resmi untuk client Sistem Absensi Self-Hosted._
