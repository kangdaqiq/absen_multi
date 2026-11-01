@echo off
cd /d "%~dp0"
if exist "venv\Scripts\activate.bat" (
    call venv\Scripts\activate.bat
)
set FACE_PROFILE=auto
set FACE_MODEL_NAME=buffalo_l
set PORT=8008
echo Starting Face Recognition Engine on http://127.0.0.1:%PORT% ...
uvicorn main:app --host 127.0.0.1 --port %PORT% --workers 1 --limit-concurrency 16 --timeout-keep-alive 5
