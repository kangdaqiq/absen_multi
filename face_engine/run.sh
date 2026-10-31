#!/usr/bin/env bash
# Script to run Face Recognition Engine on Ubuntu/Debian/Armbian STB Server
cd "$(dirname "$0")"

# Activate virtualenv if present
if [ -d "venv" ]; then
    source venv/bin/activate
fi

# Optimasi allocator glibc Linux agar tidak menahan banyak memory arena (sangat krusial untuk STB 1GB/2GB RAM)
export MALLOC_ARENA_MAX="${MALLOC_ARENA_MAX:-2}"
export FACE_PROFILE="${FACE_PROFILE:-auto}"
export FACE_MODEL_NAME="${FACE_MODEL_NAME:-buffalo_l}"

# Run uvicorn on port 8000 (single worker process + internal bounded async threadpool)
exec uvicorn main:app --host 127.0.0.1 --port 8000 --workers 1 --limit-concurrency 16 --timeout-keep-alive 5
