import os
import json
import logging
import numpy as np
import cv2
from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
import insightface
from insightface.app import FaceAnalysis

# Inisialisasi format logging standar untuk systemd & console
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] [FACE-ENGINE] %(message)s"
)
logger = logging.getLogger("face_engine")

app = FastAPI(
    title="Jagat Absen Face Recognition Engine",
    description="Microservice pengenalan wajah, deteksi ketajaman (blur), filtering pose, & ekstraksi vektor embedding berbasis InsightFace ArcFace",
    version="1.1.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Konfigurasi Model InsightFace (Default: buffalo_l ResNet-50 untuk akurasi maksimal)
MODEL_NAME = os.getenv("FACE_MODEL_NAME", "buffalo_l")
DET_SIZE = int(os.getenv("FACE_DET_SIZE", "640"))
CTX_ID = int(os.getenv("FACE_CTX_ID", "-1")) # -1 for CPU, 0 for CUDA GPU

# Threshold Kemiripan Cosine Similarity (Tersinkronisasi 0.68)
THRESHOLD = float(os.getenv("FACE_THRESHOLD", "0.68"))

# Konfigurasi Quality Check: Blur & Pose
MIN_BLUR_SCORE = float(os.getenv("FACE_MIN_BLUR", "60.0"))    # Nilai variansi Laplacian minimum
MAX_YAW = float(os.getenv("FACE_MAX_YAW", "28.0"))            # Maksimal menoleh kiri/kanan (derajat)
MAX_PITCH = float(os.getenv("FACE_MAX_PITCH", "25.0"))        # Maksimal menunduk/mendongak (derajat)
MAX_ROLL = float(os.getenv("FACE_MAX_ROLL", "30.0"))          # Maksimal miring menyamping (derajat)

print(f"Loading InsightFace model '{MODEL_NAME}' on ctx_id={CTX_ID}...")
try:
    face_app = FaceAnalysis(name=MODEL_NAME)
    face_app.prepare(ctx_id=CTX_ID, det_size=(DET_SIZE, DET_SIZE))
except Exception as e:
    print(f"Warning: Failed to load '{MODEL_NAME}' ({e}). Falling back to 'buffalo_s'...")
    face_app = FaceAnalysis(name="buffalo_s")
    face_app.prepare(ctx_id=-1, det_size=(640, 640))
print("InsightFace model initialized successfully.")

def check_face_blur(img: np.ndarray, bbox=None) -> tuple[float, bool]:
    """
    Menghitung skor ketajaman foto menggunakan Laplacian Variance.
    Jika bbox wajah tersedia, perhitungan difokuskan pada area wajah (face ROI).
    Skor < MIN_BLUR_SCORE mengindikasikan foto goyang/buram.
    """
    try:
        if bbox is not None:
            h, w = img.shape[:2]
            x1, y1, x2, y2 = [int(v) for v in bbox]
            pad_w = int((x2 - x1) * 0.1)
            pad_h = int((y2 - y1) * 0.1)
            x1 = max(0, x1 - pad_w)
            y1 = max(0, y1 - pad_h)
            x2 = min(w, x2 + pad_w)
            y2 = min(h, y2 + pad_h)

            face_roi = img[y1:y2, x1:x2]
            if face_roi.size > 0 and face_roi.shape[0] >= 20 and face_roi.shape[1] >= 20:
                gray = cv2.cvtColor(face_roi, cv2.COLOR_BGR2GRAY)
            else:
                gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        else:
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        variance = float(cv2.Laplacian(gray, cv2.CV_64F).var())
        is_clear = variance >= MIN_BLUR_SCORE
        return round(variance, 2), is_clear
    except Exception:
        return 999.0, True

def check_face_pose(face) -> tuple[bool, str, dict]:
    """
    Memeriksa orientasi wajah (yaw, pitch, roll).
    Memastikan wajah menghadap lurus ke depan, bukan menoleh samping atau mendongak/menunduk tajam.
    """
    pose = getattr(face, 'pose', None)
    if pose is None and isinstance(face, dict):
        pose = face.get('pose')

    pose_dict = {"pitch": 0.0, "yaw": 0.0, "roll": 0.0}

    if pose is not None:
        try:
            pitch = float(pose[0])
            yaw = float(pose[1])
            roll = float(pose[2])
            pose_dict = {
                "pitch": round(pitch, 2),
                "yaw": round(yaw, 2),
                "roll": round(roll, 2)
            }

            if abs(yaw) > MAX_YAW:
                arah = "kanan" if yaw > 0 else "kiri"
                return False, f"Wajah terlalu menoleh ke {arah}. Harap posisikan wajah lurus menghadap kamera.", pose_dict

            if abs(pitch) > MAX_PITCH:
                arah = "atas" if pitch > 0 else "bawah"
                return False, f"Wajah terlalu miring ke {arah}. Harap tatap kamera secara lurus.", pose_dict

            if abs(roll) > MAX_ROLL:
                return False, f"Wajah terlalu miring menyamping. Harap tegakkan posisi kepala Anda.", pose_dict

            return True, "", pose_dict
        except Exception:
            pass

    # Fallback jika model pose tidak aktif: Verifikasi kesimetrisan posisi hidung relatif terhadap mata (5 landmarks)
    kps = getattr(face, 'kps', None)
    if kps is not None and len(kps) >= 5:
        try:
            left_eye = kps[0]
            right_eye = kps[1]
            nose = kps[2]
            eye_dist = np.linalg.norm(right_eye - left_eye)
            if eye_dist > 10:
                eye_mid_x = (left_eye[0] + right_eye[0]) / 2.0
                nose_rel_x = (nose[0] - eye_mid_x) / eye_dist
                if abs(nose_rel_x) > 0.45:
                    arah = "kanan" if nose_rel_x > 0 else "kiri"
                    return False, f"Wajah terlalu menoleh ke {arah}. Harap hadap lurus ke kamera.", pose_dict
        except Exception:
            pass

    return True, "", pose_dict

@app.get("/")
@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "service": "Jagat Absen Face Recognition Engine",
        "version": "1.1.0",
        "model": MODEL_NAME,
        "device": "CPU" if CTX_ID < 0 else f"CUDA:{CTX_ID}",
        "threshold": THRESHOLD,
        "quality_filter": {
            "min_blur_score": MIN_BLUR_SCORE,
            "max_yaw": MAX_YAW,
            "max_pitch": MAX_PITCH,
            "max_roll": MAX_ROLL
        }
    }

@app.post("/extract-face-vector")
async def extract_vector(photo: UploadFile = File(...)):
    """Mengekstrak 512 angka penanda wajah dari foto master dengan validasi ketajaman & pose"""
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
                "message": "Wajah tidak terdeteksi pada foto. Pastikan wajah terlihat jelas menghadap kamera."
            }

        # Urutkan berdasarkan luas bounding box agar wajah utama/terbesar yang diambil
        faces = sorted(
            faces,
            key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
            reverse=True
        )
        target_face = faces[0]

        # 1. Validasi Ketajaman Foto (Blur Detection)
        blur_score, is_clear = check_face_blur(img, target_face.bbox)
        if not is_clear:
            return {
                "success": False,
                "message": f"Foto master wajah terlalu buram/goyang (skor: {blur_score}). Harap ambil foto ulang dengan pencahayaan cukup dan kamera stabil.",
                "blur_score": blur_score
            }

        # 2. Validasi Orientasi Wajah (Pose Filtering)
        is_pose_valid, pose_error, pose_info = check_face_pose(target_face)
        if not is_pose_valid:
            return {
                "success": False,
                "message": pose_error,
                "pose": pose_info
            }

        embedding = target_face.embedding.tolist()
        return {
            "success": True,
            "embedding": embedding,
            "face_count": len(faces),
            "blur_score": blur_score,
            "pose": pose_info
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
    """Mencocokkan foto selfie absen dengan vector master di database dengan validasi blur & pose"""
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
            logger.warning("[COMPARE] Gagal: Format gambar selfie tidak valid atau rusak (Kemiripan: 0.00%)")
            return {
                "success": False,
                "message": "Format gambar selfie tidak valid atau rusak",
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        faces = face_app.get(img)
        if len(faces) == 0:
            logger.warning("[COMPARE] Gagal: Wajah tidak terdeteksi saat absen (Kemiripan: 0.00%)")
            return {
                "success": False,
                "message": "Wajah tidak terdeteksi saat absen. Pastikan wajah terlihat jelas di layar.",
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        # Ambil wajah utama / terbesar pada selfie absen
        faces = sorted(
            faces,
            key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
            reverse=True
        )
        target_face = faces[0]

        # 1. Validasi Ketajaman Foto (Blur Detection)
        blur_score, is_clear = check_face_blur(img, target_face.bbox)
        if not is_clear:
            logger.warning(f"[COMPARE] Gagal: Foto selfie absen terlalu buram (Skor blur: {blur_score} < {MIN_BLUR_SCORE}) (Kemiripan: 0.00%)")
            return {
                "success": False,
                "message": f"Foto selfie absen terlalu buram. Harap tahan kamera lebih tenang dan coba lagi.",
                "blur_score": blur_score,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        # 2. Validasi Orientasi Wajah (Pose Filtering)
        is_pose_valid, pose_error, pose_info = check_face_pose(target_face)
        if not is_pose_valid:
            logger.warning(f"[COMPARE] Gagal: {pose_error} (Kemiripan: 0.00%)")
            return {
                "success": False,
                "message": pose_error,
                "pose": pose_info,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        checkin_embedding = np.array(target_face.embedding, dtype=np.float32)

        # Hitung kemiripan Cosine Similarity
        norm_master = np.linalg.norm(master_embedding)
        norm_checkin = np.linalg.norm(checkin_embedding)
        if norm_master == 0 or norm_checkin == 0:
            similarity = 0.0
        else:
            similarity = float(np.dot(master_embedding, checkin_embedding) / (norm_master * norm_checkin))

        # Threshold sinkronisasi 0.68
        is_match = bool(similarity >= THRESHOLD)
        sim_percent = round(similarity * 100, 2)
        threshold_percent = round(THRESHOLD * 100, 1)

        # LOG LIVE PENCOCOKAN WAJAH DENGAN PRESENTASE KEMIRIPAN
        if is_match:
            logger.info(
                f"[COMPARE] [COCOK / MATCH] Kemiripan: {sim_percent}% (Skor: {similarity:.4f} | Min Threshold: {threshold_percent}%) | Blur Score: {blur_score}"
            )
        else:
            logger.warning(
                f"[COMPARE] [TIDAK COCOK / MISMATCH] Kemiripan: {sim_percent}% (Skor: {similarity:.4f} | Min Threshold: {threshold_percent}%) | Blur Score: {blur_score}"
            )

        return {
            "success": True,
            "similarity": round(float(similarity), 4),
            "similarity_percent": sim_percent,
            "is_match": is_match,
            "threshold": THRESHOLD,
            "threshold_percent": threshold_percent,
            "blur_score": blur_score,
            "pose": pose_info
        }
    except Exception as e:
        logger.error(f"[COMPARE] Terjadi exception saat komparasi: {str(e)}")
        return {
            "success": False,
            "message": f"Gagal membandingkan wajah: {str(e)}",
            "similarity": 0.0,
            "similarity_percent": 0.0,
            "is_match": False
        }
