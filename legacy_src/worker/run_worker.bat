@echo off
TITLE Absen WA Worker
echo Starting Worker...
:loop
"c:\xampp\php\php.exe" worker.php
echo Worker crashed or stopped. Restarting in 5 seconds...
timeout /t 5
goto loop
