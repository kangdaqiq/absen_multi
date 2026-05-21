@echo off
cd /d C:\xampp\htdocs\absen
php artisan schedule:run 1>> NUL 2>&1
