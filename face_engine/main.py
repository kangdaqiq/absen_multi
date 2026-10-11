import os
import json
import numpy as np
import cv2
from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
import insightface
from insightface.app import FaceAnalysis

app = FastAPI(
    title="Jagat Absen Face Recognition Engine",
    description="Microservice pengenalan wajah & ekstraksi vektor embedding berbasis InsightFace ArcFace",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Load model ArcFace (Sangat cepat & akurat)
# Default 'buffalo_s' sangat ringan dan cepat untuk CPU VPS. Model 'buffalo_l' untuk akurasi maksimal.
MODEL_NAME = os.getenv("FACE_MODEL_NAME", "buffalo_s")
DET_SIZE = int(os.getenv("FACE_DET_SIZE", "640"))
CTX_ID = int(os.getenv("FACE_CTX_ID", "-1")) # -1 for CPU, 0 for CUDA GPU

print(f"Loading InsightFace model '{MODEL_NAME}' on ctx_id={CTX_ID}...")
try:
    face_app = FaceAnalysis(name=MODEL_NAME)
    face_app.prepare(ctx_id=CTX_ID, det_size=(DET_SIZE, DET_SIZE))
except Exception as e:
    print(f"Warning: Failed to load '{MODEL_NAME}' ({e}). Falling back to 'buffalo_s'...")
    face_app = FaceAnalysis(name="buffalo_s")
    face_app.prepare(ctx_id=-1, det_size=(640, 640))
print("InsightFace model initialized successfully.")

@app.get("/")
@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "service": "Jagat Absen Face Recognition Engine",
        "model": MODEL_NAME,
        "device": "CPU" if CTX_ID < 0 else f"CUDA:{CTX_ID}"
    }

@app.post("/extract-face-vector")
async def extract_vector(photo: UploadFile = File(...)):
    """Mengekstrak 512 angka penanda wajah dari foto master"""
    try:
        contents = await photo.read()
        nparr = np.frombuffer(contents, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            return {
                "success": False,
                "message": "Format gambar tidak valid atau rusak"
            }

        faces = face_app.get(img)
        if len(faces) == 0:
            return {
                "success": False,
                "message": "Wajah tidak terdeteksi pada foto"
            }

        # Urutkan berdasarkan luas bounding box agar wajah utama/terbesar yang diambil
        faces = sorted(
            faces,
            key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
            reverse=True
        )

        embedding = faces[0].embedding.tolist()
        return {
            "success": True,
            "embedding": embedding,
            "face_count": len(faces)
        }
    except Exception as e:
        return {
            "success": False,
            "message": f"Gagal mengekstrak vektor wajah: {str(e)}"
        }

@app.post("/compare-face")
async def compare_face(
    master_embedding_json: str = Form(...),
    checkin_photo: UploadFile = File(...)
):
    """Mencocokkan foto selfie absen dengan vector master di database"""
    try:
        if isinstance(master_embedding_json, str):
            master_raw = json.loads(master_embedding_json)
        else:
            master_raw = master_embedding_json

        master_embedding = np.array(master_raw, dtype=np.float32)

        contents = await checkin_photo.read()
        nparr = np.frombuffer(contents, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            return {
                "success": False,
                "message": "Format gambar selfie tidak valid atau rusak",
                "similarity": 0.0,
                "is_match": False
            }

        faces = face_app.get(img)
        if len(faces) == 0:
            return {
                "success": False,
                "message": "Wajah tidak terdeteksi saat absen",
                "similarity": 0.0,
                "is_match": False
            }

        # Ambil wajah utama / terbesar pada selfie absen
        faces = sorted(
            faces,
            key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
            reverse=True
        )
        checkin_embedding = np.array(faces[0].embedding, dtype=np.float32)

        # Hitung kemiripan Cosine Similarity
        norm_master = np.linalg.norm(master_embedding)
        norm_checkin = np.linalg.norm(checkin_embedding)
        if norm_master == 0 or norm_checkin == 0:
            similarity = 0.0
        else:
            similarity = float(np.dot(master_embedding, checkin_embedding) / (norm_master * norm_checkin))

        # Threshold kemiripan 75% (0.75) sesuai standar cetak biru
        THRESHOLD = float(os.getenv("FACE_THRESHOLD", "0.75"))
        is_match = bool(similarity >= THRESHOLD)

        return {
            "success": True,
            "similarity": round(float(similarity), 4),
            "is_match": is_match,
            "threshold": THRESHOLD
        }
    except Exception as e:
        return {
            "success": False,
            "message": f"Gagal membandingkan wajah: {str(e)}",
            "similarity": 0.0,
            "is_match": False
        }
