Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "cmd /c cd /d C:\xampp\htdocs\absen && php artisan serve --host=127.0.0.1 --port=8000", 0
Set WshShell = Nothing