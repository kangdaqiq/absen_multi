#!/bin/sh
set -e

echo "======================================================"
echo " Sistem Absensi — Self Hosted Edition"
echo " Starting up..."
echo "======================================================"

# ── 1. Wait for MySQL ─────────────────────────────────────────────────────
echo "[1/5] Menunggu database siap..."
until mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" > /dev/null 2>&1; do
    echo "      Database belum siap, coba lagi dalam 3 detik..."
    sleep 3
done
echo "      ✓ Database siap."

# ── 2. Run migrations ─────────────────────────────────────────────────────
echo "[2/5] Menjalankan database migrations..."
php artisan migrate --force --no-interaction
echo "      ✓ Migrations selesai."

# ── 3. Optimize app ───────────────────────────────────────────────────────
echo "[3/5] Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true
echo "      ✓ Optimization selesai."

# ── 4. Validate License (only in self_hosted mode) ────────────────────────
APP_MODE_VALUE=$(php artisan tinker --execute="echo config('app.mode');" 2>/dev/null | tr -d '\n' || echo "hosted")
if [ "$APP_MODE_VALUE" = "self_hosted" ]; then
    echo "[4/5] Memvalidasi lisensi..."
    if php artisan license:validate; then
        echo "      ✓ Lisensi valid."
    else
        echo ""
        echo "======================================================"
        echo " ⚠️  PERINGATAN: Lisensi tidak dapat divalidasi."
        echo "    Aplikasi tetap berjalan (grace period aktif)."
        echo "    Hubungi provider jika masalah berlanjut."
        echo "======================================================"
    fi
else
    echo "[4/5] Mode: hosted — validasi lisensi dilewati."
fi

# ── 5. Start all services via supervisord ────────────────────────────────
echo "[5/5] Memulai services (nginx + php-fpm + scheduler + queue)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
