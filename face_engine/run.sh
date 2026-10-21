#!/usr/bin/env bash
# Script to run Face Recognition Engine on Ubuntu/Debian Server
cd "$(dirname "$0")"

# Activate virtualenv if present
if [ -d "venv" ]; then
    source venv/bin/activate
fi

# Default model buffalo_l (ResNet-50)
export FACE_MODEL_NAME="${FACE_MODEL_NAME:-buffalo_l}"

# Run uvicorn on port 8000
exec uvicorn main:app --host 127.0.0.1 --port 8000 --workers 1
