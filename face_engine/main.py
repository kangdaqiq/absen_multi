import os
import sys
import gc
import json
import time
import ctypes
import hashlib
import logging
import platform
import threading
import asyncio
from collections import OrderedDict
from concurrent.futures import ThreadPoolExecutor
from typing import Optional

# ==============================================================================
# 1. DETEKSI PROFIL HARDWARE OTOMATIS (STB ARMBIAN / LOW-SPEC vs SERVER STANDAR)
#    Harus dijalankan SEBELUM import numpy, cv2, dan onnxruntime agar variabel
#    environment thread pool (OpenMP / OpenBLAS) langsung efektif.
# ==============================================================================
def get_total_ram_mb() -> int:
    """Mendeteksi kapasitas total RAM fisik dalam MB (lintas platform Linux & Windows)."""
    try:
        if os.path.exists("/proc/meminfo"):
            with open("/proc/meminfo", "r") as f:
                for line in f:
                    if line.startswith("MemTotal:"):
                        kb = int(line.split()[1])
                        return kb // 1024
        if sys.platform.startswith("win"):
            class MEMORYSTATUSEX(ctypes.Structure):
                _fields_ = [
                    ("dwLength", ctypes.c_ulong),
                    ("dwMemoryLoad", ctypes.c_ulong),
                    ("ullTotalPhys", ctypes.c_ulonglong),
                    ("ullAvailPhys", ctypes.c_ulonglong),
                    ("ullTotalPageFile", ctypes.c_ulonglong),
                    ("ullAvailPageFile", ctypes.c_ulonglong),
                    ("ullTotalVirtual", ctypes.c_ulonglong),
                    ("ullAvailVirtual", ctypes.c_ulonglong),
                    ("sullAvailExtendedVirtual", ctypes.c_ulonglong),
                ]
            stat = MEMORYSTATUSEX()
            stat.dwLength = ctypes.sizeof(stat)
            ctypes.windll.kernel32.GlobalMemoryStatusEx(ctypes.byref(stat))
            return int(stat.ullTotalPhys // (1024 * 1024))
    except Exception:
        pass
    return 4096


CPU_ARCH = platform.machine().lower()
CPU_CORES = os.cpu_count() or 2
TOTAL_RAM_MB = get_total_ram_mb()
IS_ARM_DEVICE = any(k in CPU_ARCH for k in ("aarch64", "arm", "armv7", "armv8"))

FACE_PROFILE = os.getenv("FACE_PROFILE", "auto").strip().lower()
if FACE_PROFILE == "low_spec":
    LOW_SPEC_MODE = True
elif FACE_PROFILE == "standard":
    LOW_SPEC_MODE = False
else:
    # Mode 'auto': aktifkan optimasi agresif jika berjalan di STB ARM, RAM <= 2.6GB, atau CPU <= 2 core
    LOW_SPEC_MODE = bool(IS_ARM_DEVICE or TOTAL_RAM_MB <= 2600 or CPU_CORES <= 2)

# Batasi jumlah thread perhitungan matriks agar tidak membuat CPU STB panas / mengganggu Nginx & MySQL
DEFAULT_ORT_THREADS = 2 if LOW_SPEC_MODE else min(4, max(2, CPU_CORES // 2))
ORT_THREADS = int(os.getenv("FACE_ORT_THREADS", str(DEFAULT_ORT_THREADS)))

for env_var in ("OMP_NUM_THREADS", "OPENBLAS_NUM_THREADS", "MKL_NUM_THREADS", "VECLIB_MAXIMUM_THREADS", "NUMEXPR_NUM_THREADS"):
    os.environ.setdefault(env_var, str(ORT_THREADS))

import numpy as np
import cv2
import onnxruntime as ort
from fastapi import FastAPI, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware

# Matikan multi-threading internal OpenCV agar tidak berebut core dengan ONNX Runtime
try:
    cv2.setNumThreads(1)
except Exception:
    pass


# ==============================================================================
# 2. TUNING ONNX RUNTIME SESSION (HEMAT RAM & 0% CPU SPINNING SAAT IDLE)
# ==============================================================================
_orig_ort_init = ort.InferenceSession.__init__


def _tuned_ort_init(self, path_or_bytes, sess_options=None, providers=None, **kwargs):
    if sess_options is None:
        sess_options = ort.SessionOptions()
    sess_options.intra_op_num_threads = ORT_THREADS
    sess_options.inter_op_num_threads = 1
    sess_options.execution_mode = ort.ExecutionMode.ORT_SEQUENTIAL
    sess_options.graph_optimization_level = ort.GraphOptimizationLevel.ORT_ENABLE_ALL

    # Matikan thread spinning agar CPU langsung turun ke 0% begitu inferensi selesai (sangat penting untuk STB tanpa kipas)
    try:
        sess_options.add_session_config_entry("session.intra_op.allow_spinning", "0")
        sess_options.add_session_config_entry("session.inter_op.allow_spinning", "0")
    except Exception:
        pass

    # Pada STB / Low-Spec Server, matikan Memory Arena yang menahan ratusan MB RAM secara permanen
    if LOW_SPEC_MODE:
        sess_options.enable_cpu_mem_arena = False
        sess_options.enable_mem_pattern = False

    return _orig_ort_init(self, path_or_bytes, sess_options=sess_options, providers=providers, **kwargs)


ort.InferenceSession.__init__ = _tuned_ort_init

import insightface
from insightface.app import FaceAnalysis

# Fungsi pembersih memori heap Linux (malloc_trim) agar RAM yang selesai dipakai dikembalikan ke OS
_libc = None
if sys.platform.startswith("linux"):
    try:
        _libc = ctypes.CDLL("libc.so.6")
    except Exception:
        _libc = None


def release_unused_memory():
    """Memaksa garbage collector & glibc mengembalikan halaman memori bebas ke kernel Linux."""
    try:
        gc.collect()
        if _libc is not None:
            _libc.malloc_trim(0)
    except Exception:
        pass


# Inisialisasi format logging standar untuk systemd & console
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] [FACE-ENGINE] %(message)s"
)
logger = logging.getLogger("face_engine")

app = FastAPI(
    title="Jagat Absen Face Recognition Engine",
    description="Microservice pengenalan wajah 1:1 & 1:N (NumPy Matrix), CLAHE Auto-Lighting, Centroid Multi-Template & STB Low-Spec Optimizer",
    version="1.3.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Konfigurasi Model & Resolusi Deteksi sesuai Profil Perangkat
MODEL_NAME = os.getenv("FACE_MODEL_NAME", "buffalo_l")
DEFAULT_DET_SIZE = 480 if LOW_SPEC_MODE else 640
DET_SIZE = int(os.getenv("FACE_DET_SIZE", str(DEFAULT_DET_SIZE)))
CTX_ID = int(os.getenv("FACE_CTX_ID", "-1"))  # -1 for CPU, 0 for CUDA GPU

# Threshold Kemiripan Cosine Similarity (Tersinkronisasi 0.68)
THRESHOLD = float(os.getenv("FACE_THRESHOLD", "0.68"))

# Konfigurasi Quality Check: Blur, Pose, & Batas Ukuran Gambar
MIN_BLUR_SCORE = float(os.getenv("FACE_MIN_BLUR", "45.0"))
MAX_YAW = float(os.getenv("FACE_MAX_YAW", "28.0"))
MAX_PITCH = float(os.getenv("FACE_MAX_PITCH", "25.0"))
MAX_ROLL = float(os.getenv("FACE_MAX_ROLL", "30.0"))
DEFAULT_MAX_DIM = 800 if LOW_SPEC_MODE else 1280
MAX_IMAGE_DIM = int(os.getenv("FACE_MAX_IMAGE_DIM", str(DEFAULT_MAX_DIM)))
MAX_UPLOAD_BYTES = int(os.getenv("FACE_MAX_UPLOAD_MB", "8")) * 1024 * 1024

# Dedicated Single-Worker Executor & Bounded Queue Semaphore:
# Menjamin di setiap milidetik hanya ada 1 gambar tidak terkompresi di RAM (mencegah lonjakan OOM saat jam masuk sekolah)
INFER_WORKERS = int(os.getenv("FACE_INFER_WORKERS", "1"))
MAX_QUEUE_CONCURRENCY = int(os.getenv("FACE_MAX_QUEUE", "6" if LOW_SPEC_MODE else "12"))
QUEUE_TIMEOUT_SEC = float(os.getenv("FACE_QUEUE_TIMEOUT", "15.0"))

INFER_EXECUTOR = ThreadPoolExecutor(max_workers=INFER_WORKERS, thread_name_prefix="jagat_face_worker")
QUEUE_SEMAPHORE = asyncio.Semaphore(MAX_QUEUE_CONCURRENCY)
infer_lock = threading.Lock()
_request_counter = 0

# Cache LRU Matriks Kandidat 1:N (Menghindari parse JSON & normalisasi ulang matriks di setiap scan)
MATRIX_CACHE: OrderedDict[str, tuple[np.ndarray, list]] = OrderedDict()
MATRIX_CACHE_MAX_ITEMS = 4
matrix_cache_lock = threading.Lock()

# Hanya muat modul 'detection' dan 'recognition' (hemat ~180MB RAM karena tidak memuat 3D/2D landmarks & genderage)
ALLOWED_MODULES = ["detection", "recognition"]

logger.info(
    f"Hardware Profile: arch={CPU_ARCH}, cores={CPU_CORES}, ram={TOTAL_RAM_MB}MB | "
    f"LOW_SPEC_MODE={LOW_SPEC_MODE}, ort_threads={ORT_THREADS}, det_size={DET_SIZE}, max_dim={MAX_IMAGE_DIM}"
)
print(f"Loading InsightFace model '{MODEL_NAME}' (modules={ALLOWED_MODULES}) on ctx_id={CTX_ID}...")
try:
    face_app = FaceAnalysis(name=MODEL_NAME, allowed_modules=ALLOWED_MODULES)
    face_app.prepare(ctx_id=CTX_ID, det_size=(DET_SIZE, DET_SIZE))
except Exception as e:
    print(f"Warning: Failed to load '{MODEL_NAME}' ({e}). Falling back to 'buffalo_s'...")
    face_app = FaceAnalysis(name="buffalo_s", allowed_modules=ALLOWED_MODULES)
    face_app.prepare(ctx_id=-1, det_size=(DET_SIZE, DET_SIZE))

release_unused_memory()
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


def decode_and_resize_image(raw_bytes: bytes) -> Optional[np.ndarray]:
    """
    Mendekode byte gambar secara hemat memori (menggunakan REDUCED_COLOR_2 pada gambar raksasa)
    dan mengecilkan dimensi ke MAX_IMAGE_DIM.
    """
    if not raw_bytes:
        return None
    nparr = np.frombuffer(raw_bytes, np.uint8)
    # Jika ukuran file JPEG > 1.2 MB (biasanya foto kamera HP 12MP+), dekode langsung dengan skala 1/2 di level libjpeg
    read_flag = cv2.IMREAD_REDUCED_COLOR_2 if (LOW_SPEC_MODE and len(raw_bytes) > 1200 * 1024) else cv2.IMREAD_COLOR
    img = cv2.imdecode(nparr, read_flag)
    if img is None:
        return None

    h, w = img.shape[:2]
    max_side = max(h, w)
    if max_side > MAX_IMAGE_DIM:
        scale = MAX_IMAGE_DIM / float(max_side)
        new_w = max(1, int(w * scale))
        new_h = max(1, int(h * scale))
        img = cv2.resize(img, (new_w, new_h), interpolation=cv2.INTER_AREA)
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
        return mean_val < 92.0 or mean_val > 205.0 or std_val < 40.0
    except Exception:
        return False


def detect_faces_robust(img: np.ndarray) -> tuple[list, np.ndarray, bool]:
    """
    Mendeteksi wajah dengan fallback & pre-processing CLAHE otomatis jika kondisi pencahayaan buruk.
    Mengembalikan (faces, processed_img, clahe_applied).
    """
    clahe_applied = False

    if needs_lighting_enhancement(img):
        img_clahe = apply_clahe_bgr(img)
        with infer_lock:
            faces = face_app.get(img_clahe)
        if len(faces) > 0:
            faces = sorted(
                faces,
                key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
                reverse=True
            )
            return faces, img_clahe, True

    with infer_lock:
        faces = face_app.get(img)

    if len(faces) == 0:
        img_clahe = apply_clahe_bgr(img)
        with infer_lock:
            faces = face_app.get(img_clahe)
        if len(faces) > 0:
            faces = sorted(
                faces,
                key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
                reverse=True
            )
            return faces, img_clahe, True
        return [], img, False

    faces = sorted(
        faces,
        key=lambda x: (x.bbox[2] - x.bbox[0]) * (x.bbox[3] - x.bbox[1]),
        reverse=True
    )

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
                roll_deg = float(np.degrees(np.arctan2(dy, dx)))
                eye_mid_x = (left_eye[0] + right_eye[0]) / 2.0
                nose_rel_x = (nose[0] - eye_mid_x) / eye_dist
                yaw_deg = float(nose_rel_x * 62.0)

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


def get_or_build_candidate_matrix(candidates_json: str) -> tuple[Optional[np.ndarray], list]:
    """
    Mengambil matriks NumPy (N, 512) yang sudah dinormalisasi dari cache LRU (jika daftar kandidat belum berubah),
    atau membangun dan menyimpannya ke dalam cache.
    """
    cache_key = hashlib.md5(candidates_json.encode("utf-8")).hexdigest()
    with matrix_cache_lock:
        if cache_key in MATRIX_CACHE:
            MATRIX_CACHE.move_to_end(cache_key)
            return MATRIX_CACHE[cache_key]

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
        return None, []

    mat = np.asarray(matrix_rows, dtype=np.float32)
    norms = np.linalg.norm(mat, axis=1, keepdims=True)
    norms[norms < 1e-12] = 1.0
    mat_normed = np.ascontiguousarray(mat / norms, dtype=np.float32)

    with matrix_cache_lock:
        MATRIX_CACHE[cache_key] = (mat_normed, valid_meta)
        while len(MATRIX_CACHE) > MATRIX_CACHE_MAX_ITEMS:
            MATRIX_CACHE.popitem(last=False)

    return mat_normed, valid_meta


def _maybe_trim_memory():
    """Jalankan pembersihan memori secara berkala (setiap 10 request pada STB, atau 50 pada server standar)."""
    global _request_counter
    _request_counter += 1
    interval = 10 if LOW_SPEC_MODE else 50
    if _request_counter % interval == 0:
        release_unused_memory()


# ==============================================================================
# 3. WORKER SYNCHRONOUS FUNCTIONS (DIJALANKAN DI DALAM BOUNDED THREADPOOL)
# ==============================================================================
def _sync_extract_vector(raw_bytes: bytes, existing_embedding_json: Optional[str], blend_weight: float) -> dict:
    img = None
    proc_img = None
    try:
        img = decode_and_resize_image(raw_bytes)
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

        blur_score, is_clear = check_face_blur(proc_img, target_face.bbox)
        if not is_clear:
            return {
                "success": False,
                "message": f"Foto wajah terlalu buram/goyang (skor: {blur_score}). Harap ambil foto ulang dengan pencahayaan cukup dan kamera stabil.",
                "blur_score": blur_score
            }

        is_pose_valid, pose_error, pose_info = check_face_pose(target_face)
        if not is_pose_valid:
            return {
                "success": False,
                "message": pose_error,
                "pose": pose_info
            }

        normed_vec = normalize_vector(target_face.embedding)
        centroid_merged = False

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
    finally:
        del img, proc_img
        _maybe_trim_memory()


def _sync_compare_face(raw_bytes: bytes, master_embedding_json: str) -> dict:
    img = None
    proc_img = None
    try:
        master_raw = json.loads(master_embedding_json) if isinstance(master_embedding_json, str) else master_embedding_json
        if isinstance(master_raw, dict) and "v512" in master_raw:
            master_raw = master_raw["v512"]

        master_embedding = normalize_vector(np.array(master_raw, dtype=np.float32))

        img = decode_and_resize_image(raw_bytes)
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
    finally:
        del img, proc_img
        _maybe_trim_memory()


def _sync_identify_face(raw_bytes: bytes, candidates_json: str, active_threshold: float) -> dict:
    t0 = time.perf_counter()
    img = None
    proc_img = None
    try:
        img = decode_and_resize_image(raw_bytes)
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

        blur_score, is_clear = check_face_blur(proc_img, target_face.bbox)
        if not is_clear:
            return {
                "success": False,
                "is_match": False,
                "blur_score": blur_score,
                "message": f"Tangkapan wajah kurang tajam (skor: {blur_score}). Harap berhenti sejenak di depan kamera."
            }

        is_pose_valid, pose_error, pose_info = check_face_pose(target_face)
        if not is_pose_valid:
            return {
                "success": False,
                "is_match": False,
                "pose": pose_info,
                "message": pose_error
            }

        query_vec = normalize_vector(target_face.embedding)

        mat_normed, valid_meta = get_or_build_candidate_matrix(candidates_json)
        if mat_normed is None or len(valid_meta) == 0:
            return {
                "success": True,
                "is_match": False,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "embedding": query_vec.tolist(),
                "message": "Belum ada kandidat wajah 512-d terdaftar."
            }

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
                f"[IDENTIFY 1:N] [MATCH] ID={best_meta['id']} ({best_meta['type']}) | Skor: {sim_percent}% ({best_score:.4f}) | Kandidat: {len(valid_meta)} | Waktu: {elapsed_ms}ms | CLAHE: {clahe_used}"
            )
        else:
            logger.warning(
                f"[IDENTIFY 1:N] [NO MATCH] Skor tertinggi: {sim_percent}% ({best_score:.4f} < {active_threshold}) | Kandidat: {len(valid_meta)} | Waktu: {elapsed_ms}ms"
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
            "candidates_count": len(valid_meta),
            "elapsed_ms": elapsed_ms,
            "blur_score": blur_score,
            "pose": pose_info,
            "clahe_enhanced": clahe_used,
            "embedding": query_vec.tolist(),
            "updated_embedding": updated_centroid
        }
    finally:
        del img, proc_img
        _maybe_trim_memory()


async def _run_bounded_inference(func, *args) -> dict:
    """
    Menjalankan fungsi inferensi di dalam ThreadPoolExecutor terbatas dengan proteksi antrean (Semaphore)
    agar FastAPI Event-Loop tidak pernah terblokir dan memori STB tidak pernah meledak (OOM).
    """
    try:
        await asyncio.wait_for(QUEUE_SEMAPHORE.acquire(), timeout=QUEUE_TIMEOUT_SEC)
    except asyncio.TimeoutError:
        logger.warning("[QUEUE] Antrean Face Engine penuh, menolak request baru untuk menjaga kestabilan RAM.")
        return {
            "success": False,
            "is_match": False,
            "similarity": 0.0,
            "similarity_percent": 0.0,
            "message": "Server pengenalan wajah sedang memproses antrean padat. Silakan coba 2 detik lagi."
        }

    try:
        loop = asyncio.get_running_loop()
        return await loop.run_in_executor(INFER_EXECUTOR, func, *args)
    finally:
        QUEUE_SEMAPHORE.release()


# ==============================================================================
# 4. FASTAPI ASYNC ENDPOINTS (NON-BLOCKING EVENT LOOP)
# ==============================================================================
@app.get("/")
@app.get("/health")
async def health_check():
    return {
        "status": "ok",
        "service": "Jagat Absen Face Recognition Engine",
        "version": "1.3.0",
        "model": MODEL_NAME,
        "modules": ALLOWED_MODULES,
        "device": "CPU" if CTX_ID < 0 else f"CUDA:{CTX_ID}",
        "hardware_profile": {
            "low_spec_mode": LOW_SPEC_MODE,
            "cpu_arch": CPU_ARCH,
            "cpu_cores": CPU_CORES,
            "total_ram_mb": TOTAL_RAM_MB,
            "ort_threads": ORT_THREADS,
            "det_size": DET_SIZE,
            "max_image_dim": MAX_IMAGE_DIM,
            "infer_workers": INFER_WORKERS,
            "max_queue": MAX_QUEUE_CONCURRENCY,
            "cached_matrices": len(MATRIX_CACHE),
        },
        "threshold": THRESHOLD,
        "features": [
            "numpy_matrix_1n",
            "lru_matrix_cache",
            "clahe_lighting",
            "centroid_multitemplate",
            "stb_low_spec_optimizer",
            "bounded_async_queue"
        ],
        "quality_filter": {
            "min_blur_score": MIN_BLUR_SCORE,
            "max_yaw": MAX_YAW,
            "max_pitch": MAX_PITCH,
            "max_roll": MAX_ROLL
        }
    }


@app.post("/extract-face-vector")
async def extract_vector(
    photo: UploadFile = File(...),
    existing_embedding_json: Optional[str] = Form(None),
    blend_weight: float = Form(0.5)
):
    """
    Mengekstrak 512 angka penanda wajah (L2-normalized) dari foto master secara non-blocking
    dengan validasi ketajaman, pose, CLAHE auto-lighting, dan opsional Multi-Template Centroid.
    """
    try:
        raw_bytes = await photo.read()
        await photo.close()
        if len(raw_bytes) > MAX_UPLOAD_BYTES:
            return {
                "success": False,
                "message": "Ukuran file foto terlalu besar (maksimal 8 MB)."
            }
        return await _run_bounded_inference(_sync_extract_vector, raw_bytes, existing_embedding_json, blend_weight)
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
    """Mencocokkan 1:1 foto selfie absen dengan vector master secara non-blocking."""
    try:
        raw_bytes = await checkin_photo.read()
        await checkin_photo.close()
        if len(raw_bytes) > MAX_UPLOAD_BYTES:
            return {
                "success": False,
                "is_match": False,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "message": "Ukuran file foto selfie terlalu besar (maksimal 8 MB)."
            }
        return await _run_bounded_inference(_sync_compare_face, raw_bytes, master_embedding_json)
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
async def identify_face(
    candidates_json: str = Form(...),
    photo: UploadFile = File(...),
    threshold: Optional[float] = Form(None)
):
    """
    Pencarian Wajah 1:N Super Cepat berbasis Perkalian Matriks NumPy + In-Memory LRU Matrix Cache.
    Non-blocking terhadap event loop FastAPI dan aman dari lonjakan RAM pada STB.
    """
    active_threshold = float(threshold) if threshold is not None else THRESHOLD
    try:
        raw_bytes = await photo.read()
        await photo.close()
        if len(raw_bytes) > MAX_UPLOAD_BYTES:
            return {
                "success": False,
                "is_match": False,
                "similarity": 0.0,
                "similarity_percent": 0.0,
                "message": "Ukuran file foto terlalu besar (maksimal 8 MB)."
            }
        return await _run_bounded_inference(_sync_identify_face, raw_bytes, candidates_json, active_threshold)
    except Exception as e:
        logger.error(f"[IDENTIFY 1:N] Exception: {str(e)}")
        return {
            "success": False,
            "is_match": False,
            "similarity": 0.0,
            "similarity_percent": 0.0,
            "message": f"Gagal mengidentifikasi wajah: {str(e)}"
        }
