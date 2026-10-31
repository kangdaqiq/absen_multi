import os
import json
import time
import logging
import threading
import numpy as np
import cv2
from typing import Optional
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
    description="Microservice pengenalan wajah 1:1 & 1:N (NumPy Matrix), CLAHE Auto-Lighting, Centroid Multi-Template, Blur & Pose Filter berbasis InsightFace ArcFace",
    version="1.2.0"
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
CTX_ID = int(os.getenv("FACE_CTX_ID", "-1"))  # -1 for CPU, 0 for CUDA GPU

# Threshold Kemiripan Cosine Similarity (Tersinkronisasi 0.68)
THRESHOLD = float(os.getenv("FACE_THRESHOLD", "0.68"))

# Konfigurasi Quality Check: Blur & Pose
MIN_BLUR_SCORE = float(os.getenv("FACE_MIN_BLUR", "45.0"))    # Nilai variansi Laplacian minimum (ramah webcam/kiosk)
MAX_YAW = float(os.getenv("FACE_MAX_YAW", "28.0"))            # Maksimal menoleh kiri/kanan (derajat)
MAX_PITCH = float(os.getenv("FACE_MAX_PITCH", "25.0"))        # Maksimal menunduk/mendongak (derajat)
MAX_ROLL = float(os.getenv("FACE_MAX_ROLL", "30.0"))          # Maksimal miring menyamping (derajat)
MAX_IMAGE_DIM = int(os.getenv("FACE_MAX_IMAGE_DIM", "1280"))  # Downscale foto kamera HP beresolusi tinggi agar cepat

# Lock untuk keamanan konkurensi ONNX Runtime di multi-thread
infer_lock = threading.Lock()

# Hanya muat modul 'detection' dan 'recognition' (hemat ~180MB RAM & 2x lebih cepat karena tidak memuat 3D/2D landmarks & genderage)
ALLOWED_MODULES = ["detection", "recognition"]

print(f"Loading InsightFace model '{MODEL_NAME}' (modules={ALLOWED_MODULES}) on ctx_id={CTX_ID}...")
try:
    face_app = FaceAnalysis(name=MODEL_NAME, allowed_modules=ALLOWED_MODULES)
    face_app.prepare(ctx_id=CTX_ID, det_size=(DET_SIZE, DET_SIZE))
except Exception as e:
    print(f"Warning: Failed to load '{MODEL_NAME}' ({e}). Falling back to 'buffalo_s'...")
    face_app = FaceAnalysis(name="buffalo_s", allowed_modules=ALLOWED_MODULES)
    face_app.prepare(ctx_id=-1, det_size=(640, 640))
print("InsightFace model initialized successfully.")


def normalize_vector(vec: np.ndarray) -> np.ndarray:
    """Normalisasi L2 pada vektor 512-d agar Cosine Similarity cukup menggunakan dot product."""
    arr = np.asarray(vec, dtype=np.float32).flatten()
    norm = float(np.linalg.norm(arr))
    if norm > 1e-12:
        return arr / norm
    return arr


def blend_centroid(existing_vec: np.ndarray, new_vec: np.ndarray, weight_new: float = 0.5) -> np.ndarray:
    """
    Menggabungkan vektor wajah lama dan baru menjadi Normalized Centroid Vector.
    Memungkinkan multi-template (berbagai kondisi cahaya/kacamata) tetap dalam 1 vektor 512-d.
    """
    v_old = normalize_vector(existing_vec)
    v_new = normalize_vector(new_vec)
    if v_old.shape != v_new.shape:
        return v_new
    w = float(np.clip(weight_new, 0.05, 0.95))
    combined = ((1.0 - w) * v_old) + (w * v_new)
    return normalize_vector(combined)


def resize_if_needed(img: np.ndarray) -> np.ndarray:
    """Kecilkan gambar jika dimensinya melebihi MAX_IMAGE_DIM (misal foto 4000x3000 dari HP)."""
    h, w = img.shape[:2]
    max_side = max(h, w)
    if max_side > MAX_IMAGE_DIM:
        scale = MAX_IMAGE_DIM / float(max_side)
        new_w = max(1, int(w * scale))
        new_h = max(1, int(h * scale))
        return cv2.resize(img, (new_w, new_h), interpolation=cv2.INTER_AREA)
    return img


def apply_clahe_bgr(img: np.ndarray) -> np.ndarray:
    """Menerapkan CLAHE pada channel Luminance (LAB) untuk menormalisasi cahaya gelap atau backlight."""
    try:
        lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
        l_chan, a_chan, b_chan = cv2.split(lab)
        clahe = cv2.createCLAHE(clipLimit=2.2, tileGridSize=(8, 8))
        l_clahe = clahe.apply(l_chan)
        merged = cv2.merge((l_clahe, a_chan, b_chan))
        return cv2.cvtColor(merged, cv2.COLOR_LAB2BGR)
    except Exception:
        return img


def needs_lighting_enhancement(img: np.ndarray, bbox=None) -> bool:
    """
    Mendeteksi secara cepat apakah gambar/area wajah terlalu gelap, terlalu terang (overexposed),
    atau kontras rendah akibat cahaya latar (backlight).
    """
    try:
        roi = img
        if bbox is not None:
            h, w = img.shape[:2]
            x1, y1, x2, y2 = [max(0, int(v)) for v in bbox]
            x2, y2 = min(w, x2), min(h, y2)
            if (x2 - x1) >= 20 and (y2 - y1) >= 20:
                roi = img[y1:y2, x1:x2]

        gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
        mean_val = float(np.mean(gray))
        std_val = float(np.std(gray))
        # Gelap (< 92), silau (> 205), atau kontras datar/backlight (std < 40)
        return mean_val < 92.0 or mean_val > 205.0 or std_val < 40.0
    except Exception:
        return False


def detect_faces_robust(img: np.ndarray) -> tuple[list, np.ndarray, bool]:
    """
    Mendeteksi wajah dengan fallback & pre-processing CLAHE otomatis jika kondisi pencahayaan buruk.
    Mengembalikan (faces, processed_img, clahe_applied).
    """
    img = resize_if_needed(img)
    clahe_applied = False

    # Jika gambar secara keseluruhan sangat gelap/backlight, terapkan CLAHE lebih dulu
    if needs_lighting_enhancement(img):
        img_clahe = apply_clahe_bgr(img)
        with infer_lock:
            faces = face_app.get(img_clahe)
        if len(faces) > 0:
            return faces, img_clahe, True

    # Deteksi standar
    with infer_lock:
        faces = face_app.get(img)

    # Jika tidak terdeteksi wajah pada gambar normal, coba sekali lagi dengan CLAHE
    if len(faces) == 0:
        img_clahe = apply_clahe_bgr(img)
        with infer_lock:
            faces = face_app.get(img_clahe)
        if len(faces) > 0:
            return faces, img_clahe, True
        return [], img, False

    # Urutkan wajah terbesar terlebih dahulu
    faces = sorted(
        faces,
        key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
        reverse=True
    )

    # Jika area spesifik wajah (ROI) gelap/backlight padahal background terang, re-ekstrak dengan CLAHE
    if needs_lighting_enhancement(img, faces[0].bbox):
        img_clahe = apply_clahe_bgr(img)
        with infer_lock:
            faces_clahe = face_app.get(img_clahe)
        if len(faces_clahe) > 0:
            faces_clahe = sorted(
                faces_clahe,
                key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
                reverse=True
            )
            return faces_clahe, img_clahe, True

    return faces, img, clahe_applied


def check_face_blur(img: np.ndarray, bbox=None) -> tuple[float, bool]:
    """
    Menghitung skor ketajaman foto menggunakan Laplacian Variance pada ROI wajah yang dinormalisasi ukurannya.
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

        # Normalisasi ukuran ROI ke 160x160 agar skor Laplacian konsisten baik pada kamera 480p maupun 4K
        gray_norm = cv2.resize(gray, (160, 160), interpolation=cv2.INTER_LINEAR)
        variance = float(cv2.Laplacian(gray_norm, cv2.CV_64F).var())
        is_clear = variance >= MIN_BLUR_SCORE
        return round(variance, 2), is_clear
    except Exception:
        return 999.0, True


def check_face_pose(face) -> tuple[bool, str, dict]:
    """
    Memeriksa orientasi wajah (yaw, pitch, roll).
    Mendukung perhitungan cepat berbasis 5 keypoints (tanpa perlu model 3D landmark 68 titik yang berat).
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
                return False, "Wajah terlalu miring menyamping. Harap tegakkan posisi kepala Anda.", pose_dict

            return True, "", pose_dict
        except Exception:
            pass

    # Estimasi geometri presisi dari 5 titik keypoints (left_eye, right_eye, nose, left_mouth, right_mouth)
    kps = getattr(face, 'kps', None)
    if kps is not None and len(kps) >= 5:
        try:
            left_eye = kps[0]
            right_eye = kps[1]
            nose = kps[2]
            left_mouth = kps[3]
            right_mouth = kps[4]

            dx = float(right_eye[0] - left_eye[0])
            dy = float(right_eye[1] - left_eye[1])
            eye_dist = float(np.hypot(dx, dy))

            if eye_dist > 10.0:
                # 1. Roll (kemiringan garis mata dalam derajat)
                roll_deg = float(np.degrees(np.arctan2(dy, dx)))
                # 2. Yaw (rasio pergeseran hidung dari titik tengah mata, dikonversi ke estimasi derajat)
                eye_mid_x = (left_eye[0] + right_eye[0]) / 2.0
                nose_rel_x = (nose[0] - eye_mid_x) / eye_dist
                yaw_deg = float(nose_rel_x * 62.0)
                # 3. Pitch (rasio vertikal mata-ke-hidung dibanding mata-ke-mulut)
                eye_mid_y = (left_eye[1] + right_eye[1]) / 2.0
                mouth_mid_y = (left_mouth[1] + right_mouth[1]) / 2.0
                face_vert = max(10.0, float(mouth_mid_y - eye_mid_y))
                nose_vert_ratio = (nose[1] - eye_mid_y) / face_vert
                pitch_deg = float((0.56 - nose_vert_ratio) * 75.0)

                pose_dict = {
                    "pitch": round(pitch_deg, 2),
                    "yaw": round(yaw_deg, 2),
                    "roll": round(roll_deg, 2)
                }

                if abs(yaw_deg) > MAX_YAW:
                    arah = "kanan" if yaw_deg > 0 else "kiri"
                    return False, f"Wajah terlalu menoleh ke {arah}. Harap hadap lurus ke kamera.", pose_dict

                if abs(pitch_deg) > MAX_PITCH:
                    arah = "atas" if pitch_deg > 0 else "bawah"
                    return False, f"Wajah terlalu miring ke {arah}. Harap tatap kamera secara lurus.", pose_dict

                if abs(roll_deg) > MAX_ROLL:
                    return False, "Wajah terlalu miring menyamping. Harap tegakkan posisi kepala Anda.", pose_dict
        except Exception:
            pass

    return True, "", pose_dict


@app.get("/")
@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "service": "Jagat Absen Face Recognition Engine",
        "version": "1.2.0",
        "model": MODEL_NAME,
        "modules": ALLOWED_MODULES,
        "device": "CPU" if CTX_ID < 0 else f"CUDA:{CTX_ID}",
        "threshold": THRESHOLD,
        "features": ["numpy_matrix_1n", "clahe_lighting", "centroid_multitemplate"],
        "quality_filter": {
            "min_blur_score": MIN_BLUR_SCORE,
            "max_yaw": MAX_YAW,
            "max_pitch": MAX_PITCH,
            "max_roll": MAX_ROLL
        }
    }


@app.post("/extract-face-vector")
def extract_vector(
    photo: UploadFile = File(...),
    existing_embedding_json: Optional[str] = Form(None),
    blend_weight: float = Form(0.5)
):
    """
    Mengekstrak 512 angka penanda wajah (L2-normalized) dari foto master dengan validasi ketajaman,
    pose, CLAHE auto-lighting, dan opsional penggabungan Multi-Template Centroid.
    """
    try:
        contents = photo.file.read()
        nparr = np.frombuffer(contents, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            return {
                "success": False,
                "message": "Format gambar tidak valid atau rusak"
            }

        faces, proc_img, clahe_used = detect_faces_robust(img)
        if len(faces) == 0:
            return {
                "success": False,
                "message": "Wajah tidak terdeteksi pada foto. Pastikan wajah terlihat jelas menghadap kamera."
            }

        target_face = faces[0]

        # 1. Validasi Ketajaman Foto (Blur Detection)
        blur_score, is_clear = check_face_blur(proc_img, target_face.bbox)
        if not is_clear:
            return {
                "success": False,
                "message": f"Foto wajah terlalu buram/goyang (skor: {blur_score}). Harap ambil foto ulang dengan pencahayaan cukup dan kamera stabil.",
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

        normed_vec = normalize_vector(target_face.embedding)
        centroid_merged = False

        # 3. Multi-Template Centroid Enrollment (jika existing_embedding_json dikirim)
        if existing_embedding_json:
            try:
                old_raw = json.loads(existing_embedding_json) if isinstance(existing_embedding_json, str) else existing_embedding_json
                if isinstance(old_raw, dict) and "v512" in old_raw:
                    old_raw = old_raw["v512"]
                if isinstance(old_raw, list) and len(old_raw) == 512:
                    normed_vec = blend_centroid(np.array(old_raw, dtype=np.float32), normed_vec, blend_weight)
                    centroid_merged = True
            except Exception:
                pass

        return {
            "success": True,
            "embedding": normed_vec.tolist(),
            "face_count": len(faces),
            "blur_score": blur_score,
            "pose": pose_info,
            "clahe_enhanced": clahe_used,
            "centroid_merged": centroid_merged
        }
    except Exception as e:
        return {
            "success": False,
            "message": f"Gagal mengekstrak vektor wajah: {str(e)}"
        }


@app.post("/compare-face")
def compare_face(
    master_embedding_json: str = Form(...),
    checkin_photo: UploadFile = File(...)
):
    """Mencocokkan 1:1 foto selfie absen dengan vector master di database (dilengkapi CLAHE, blur & pose check)."""
    try:
        master_raw = json.loads(master_embedding_json) if isinstance(master_embedding_json, str) else master_embedding_json
        if isinstance(master_raw, dict) and "v512" in master_raw:
            master_raw = master_raw["v512"]

        master_embedding = normalize_vector(np.array(master_raw, dtype=np.float32))

        contents = checkin_photo.file.read()
        nparr = np.frombuffer(contents, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            logger.warning("[COMPARE] Gagal: Format gambar selfie tidak valid atau rusak")
            return {
                "success": False,
                "message": "Format gambar selfie tidak valid atau rusak",
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        faces, proc_img, clahe_used = detect_faces_robust(img)
        if len(faces) == 0:
            logger.warning("[COMPARE] Gagal: Wajah tidak terdeteksi saat absen")
            return {
                "success": False,
                "message": "Wajah tidak terdeteksi saat absen. Pastikan wajah terlihat jelas di layar.",
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        target_face = faces[0]

        # 1. Validasi Ketajaman Foto (Blur Detection)
        blur_score, is_clear = check_face_blur(proc_img, target_face.bbox)
        if not is_clear:
            logger.warning(f"[COMPARE] Gagal: Foto selfie absen terlalu buram (Skor blur: {blur_score} < {MIN_BLUR_SCORE})")
            return {
                "success": False,
                "message": "Foto selfie absen terlalu buram. Harap tahan kamera lebih tenang dan coba lagi.",
                "blur_score": blur_score,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        # 2. Validasi Orientasi Wajah (Pose Filtering)
        is_pose_valid, pose_error, pose_info = check_face_pose(target_face)
        if not is_pose_valid:
            logger.warning(f"[COMPARE] Gagal: {pose_error}")
            return {
                "success": False,
                "message": pose_error,
                "pose": pose_info,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "is_match": False
            }

        checkin_embedding = normalize_vector(target_face.embedding)
        similarity = float(np.dot(master_embedding, checkin_embedding))

        is_match = bool(similarity >= THRESHOLD)
        sim_percent = round(similarity * 100, 2)
        threshold_percent = round(THRESHOLD * 100, 1)

        # Jika cocok dengan keyakinan tinggi (>= 0.80), hitung kandidat vektor adaptif (running centroid)
        updated_centroid = None
        if is_match and similarity >= 0.80:
            updated_centroid = blend_centroid(master_embedding, checkin_embedding, weight_new=0.15).tolist()

        if is_match:
            logger.info(
                f"[COMPARE] [COCOK / MATCH] Kemiripan: {sim_percent}% (Skor: {similarity:.4f} | Min: {threshold_percent}%) | Blur: {blur_score} | CLAHE: {clahe_used}"
            )
        else:
            logger.warning(
                f"[COMPARE] [TIDAK COCOK / MISMATCH] Kemiripan: {sim_percent}% (Skor: {similarity:.4f} | Min: {threshold_percent}%) | Blur: {blur_score} | CLAHE: {clahe_used}"
            )

        return {
            "success": True,
            "similarity": round(float(similarity), 4),
            "similarity_percent": sim_percent,
            "is_match": is_match,
            "threshold": THRESHOLD,
            "threshold_percent": threshold_percent,
            "blur_score": blur_score,
            "pose": pose_info,
            "clahe_enhanced": clahe_used,
            "updated_embedding": updated_centroid
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


@app.post("/identify-face")
def identify_face(
    candidates_json: str = Form(...),
    photo: UploadFile = File(...),
    threshold: Optional[float] = Form(None)
):
    """
    Pencarian Wajah 1:N Super Cepat berbasis Perkalian Matriks NumPy (BLAS).
    Mengekstrak vektor wajah dari foto satu kali, lalu mencocokkan terhadap ribuan kandidat sekaligus dalam < 0.5 ms.
    """
    t0 = time.perf_counter()
    active_threshold = float(threshold) if threshold is not None else THRESHOLD

    try:
        contents = photo.file.read()
        nparr = np.frombuffer(contents, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            return {
                "success": False,
                "is_match": False,
                "message": "Format gambar tidak valid atau rusak"
            }

        faces, proc_img, clahe_used = detect_faces_robust(img)
        if len(faces) == 0:
            return {
                "success": False,
                "is_match": False,
                "message": "Wajah tidak terdeteksi di kamera. Pastikan wajah terlihat jelas."
            }

        target_face = faces[0]

        # 1. Validasi Blur
        blur_score, is_clear = check_face_blur(proc_img, target_face.bbox)
        if not is_clear:
            return {
                "success": False,
                "is_match": False,
                "blur_score": blur_score,
                "message": f" tangkapan wajah kurang tajam (skor: {blur_score}). Harap berhenti sejenak di depan kamera."
            }

        # 2. Validasi Pose
        is_pose_valid, pose_error, pose_info = check_face_pose(target_face)
        if not is_pose_valid:
            return {
                "success": False,
                "is_match": False,
                "pose": pose_info,
                "message": pose_error
            }

        query_vec = normalize_vector(target_face.embedding)

        # 3. Parse daftar kandidat dan susun Matriks NumPy (N x 512)
        raw_candidates = json.loads(candidates_json) if isinstance(candidates_json, str) else candidates_json
        valid_meta = []
        matrix_rows = []

        for item in raw_candidates:
            emb = item.get("embedding")
            if isinstance(emb, dict) and "v512" in emb:
                emb = emb["v512"]
            if isinstance(emb, list) and len(emb) == 512:
                matrix_rows.append(emb)
                valid_meta.append({
                    "id": item.get("id"),
                    "type": item.get("type", "siswa"),
                    "user_face_id": item.get("user_face_id")
                })

        if len(matrix_rows) == 0:
            return {
                "success": True,
                "is_match": False,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "embedding": query_vec.tolist(),
                "message": "Belum ada kandidat wajah 512-d terdaftar."
            }

        # Normalisasi seluruh baris matriks sekaligus (Vectorized L2 Norm)
        mat = np.asarray(matrix_rows, dtype=np.float32)
        norms = np.linalg.norm(mat, axis=1, keepdims=True)
        norms[norms < 1e-12] = 1.0
        mat_normed = mat / norms

        # Single BLAS Matrix-Vector Dot Product: (N, 512) @ (512,) -> (N,)
        scores = np.dot(mat_normed, query_vec)
        best_idx = int(np.argmax(scores))
        best_score = float(scores[best_idx])
        best_meta = valid_meta[best_idx]

        is_match = bool(best_score >= active_threshold)
        sim_percent = round(best_score * 100, 2)
        elapsed_ms = round((time.perf_counter() - t0) * 1000, 2)

        updated_centroid = None
        if is_match and best_score >= 0.80:
            updated_centroid = blend_centroid(mat_normed[best_idx], query_vec, weight_new=0.15).tolist()

        if is_match:
            logger.info(
                f"[IDENTIFY 1:N] [MATCH] ID={best_meta['id']} ({best_meta['type']}) | Skor: {sim_percent}% ({best_score:.4f}) | Kandidat: {len(matrix_rows)} | Waktu: {elapsed_ms}ms | CLAHE: {clahe_used}"
            )
        else:
            logger.warning(
                f"[IDENTIFY 1:N] [NO MATCH] Skor tertinggi: {sim_percent}% ({best_score:.4f} < {active_threshold}) | Kandidat: {len(matrix_rows)} | Waktu: {elapsed_ms}ms"
            )

        return {
            "success": True,
            "is_match": is_match,
            "matched_id": best_meta["id"] if is_match else None,
            "matched_type": best_meta["type"] if is_match else None,
            "user_face_id": best_meta["user_face_id"] if is_match else None,
            "similarity": round(best_score, 4),
            "similarity_percent": sim_percent,
            "threshold": active_threshold,
            "candidates_count": len(matrix_rows),
            "elapsed_ms": elapsed_ms,
            "blur_score": blur_score,
            "pose": pose_info,
            "clahe_enhanced": clahe_used,
            "embedding": query_vec.tolist(),
            "updated_embedding": updated_centroid
        }
    except Exception as e:
        logger.error(f"[IDENTIFY 1:N] Exception: {str(e)}")
        return {
            "success": False,
            "is_match": False,
            "similarity": 0.0,
            "similarity_percent": 0.0,
            "message": f"Gagal mengidentifikasi wajah: {str(e)}"
        }
