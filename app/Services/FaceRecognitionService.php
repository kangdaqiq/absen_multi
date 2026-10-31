<?php

namespace App\Services;

use App\Models\UserFaceData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class FaceRecognitionService
{
    /**
     * Get the configured Python Face Engine base URL
     */
    public static function getBaseUrl(): string
    {
        return rtrim(config('services.face_engine.url') ?: env('FACE_ENGINE_URL', 'http://127.0.0.1:8000'), '/');
    }

    /**
     * Get similarity threshold (default 68% / 0.68)
     */
    public static function getThreshold(): float
    {
        return (float) (config('services.face_engine.threshold') ?: env('FACE_SIMILARITY_THRESHOLD', 0.68));
    }

    /**
     * Check if Face Engine microservice is active
     */
    public static function isHealthy(): bool
    {
        try {
            $res = Http::timeout(2)->get(self::getBaseUrl() . '/health');
            return $res->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Unpack stored face_embedding JSON into ['v512' => ?array, 'v128' => ?array].
     * Supports legacy flat 512-d array, legacy flat 128-d array, and unified {'v512': [...], 'v128': [...]} format.
     */
    public static function unpackEmbedding($raw): array
    {
        $decoded = $raw;
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
        }

        if (!is_array($decoded) || empty($decoded)) {
            return ['v512' => null, 'v128' => null];
        }

        // Unified dual-dimension object format
        if (isset($decoded['v512']) || isset($decoded['v128'])) {
            $v512 = (isset($decoded['v512']) && is_array($decoded['v512']) && count($decoded['v512']) === 512)
                ? array_values(array_map('floatval', $decoded['v512']))
                : null;
            $v128 = (isset($decoded['v128']) && is_array($decoded['v128']) && count($decoded['v128']) === 128)
                ? array_values(array_map('floatval', $decoded['v128']))
                : null;
            return ['v512' => $v512, 'v128' => $v128];
        }

        // Flat sequential array format
        $count = count($decoded);
        if ($count === 512) {
            return ['v512' => array_values(array_map('floatval', $decoded)), 'v128' => null];
        }
        if ($count === 128) {
            return ['v512' => null, 'v128' => array_values(array_map('floatval', $decoded))];
        }

        return ['v512' => null, 'v128' => null];
    }

    /**
     * Pack 512-d and/or 128-d vectors into a JSON string for storage in user_face_data.face_embedding.
     */
    public static function packEmbedding(?array $v512, ?array $v128 = null): string
    {
        $clean512 = (is_array($v512) && count($v512) === 512) ? self::normalizeVector($v512) : null;
        $clean128 = (is_array($v128) && count($v128) === 128) ? array_values(array_map('floatval', $v128)) : null;

        if ($clean512 && $clean128) {
            return json_encode(['v512' => $clean512, 'v128' => $clean128]);
        }
        if ($clean512) {
            return json_encode($clean512);
        }
        if ($clean128) {
            return json_encode($clean128);
        }
        return json_encode([]);
    }

    /**
     * Normalize vector to unit L2 length (so Cosine Similarity = dot product).
     */
    public static function normalizeVector(array $vec): array
    {
        $normSq = 0.0;
        foreach ($vec as $v) {
            $f = (float) $v;
            $normSq += $f * $f;
        }
        $norm = sqrt($normSq);
        if ($norm <= 1e-12) {
            return array_values(array_map('floatval', $vec));
        }
        $out = [];
        foreach ($vec as $v) {
            $out[] = round(((float) $v) / $norm, 6);
        }
        return $out;
    }

    /**
     * Multi-Template Centroid Averaging: merges old and new vectors into a single normalized centroid vector.
     */
    public static function blendCentroid(?array $oldVec, array $newVec, float $weightNew = 0.5): array
    {
        if (empty($oldVec) || count($oldVec) !== count($newVec)) {
            return count($newVec) === 512 ? self::normalizeVector($newVec) : array_values(array_map('floatval', $newVec));
        }

        $w = max(0.05, min(0.95, $weightNew));
        $len = count($newVec);
        $merged = [];

        if ($len === 512) {
            $oldNorm = self::normalizeVector($oldVec);
            $newNorm = self::normalizeVector($newVec);
            for ($i = 0; $i < $len; $i++) {
                $merged[] = ((1.0 - $w) * $oldNorm[$i]) + ($w * $newNorm[$i]);
            }
            return self::normalizeVector($merged);
        }

        // For 128-d Euclidean descriptors (face-api.js)
        for ($i = 0; $i < $len; $i++) {
            $merged[] = round(((1.0 - $w) * (float) $oldVec[$i]) + ($w * (float) $newVec[$i]), 6);
        }
        return $merged;
    }

    /**
     * Extract 512-dimension vector embedding from master photo (with optional multi-template centroid blending)
     *
     * @param UploadedFile|string $photo
     * @param array|string|null $existingEmbedding
     * @param float $blendWeight
     * @return array ['success' => bool, 'embedding' => ?array, 'message' => ?string]
     */
    public static function extractVector($photo, $existingEmbedding = null, float $blendWeight = 0.5): array
    {
        $url = self::getBaseUrl() . '/extract-face-vector';

        try {
            $req = Http::timeout(30);

            if ($photo instanceof UploadedFile) {
                $req = $req->attach('photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName());
            } elseif (is_string($photo) && file_exists($photo)) {
                $req = $req->attach('photo', file_get_contents($photo), basename($photo));
            } else {
                return [
                    'success' => false,
                    'message' => 'File foto tidak ditemukan atau tidak valid.'
                ];
            }

            $postFields = [];
            if (!empty($existingEmbedding)) {
                $unpacked = self::unpackEmbedding($existingEmbedding);
                if (!empty($unpacked['v512'])) {
                    $postFields['existing_embedding_json'] = json_encode($unpacked['v512']);
                    $postFields['blend_weight'] = (string) $blendWeight;
                }
            }

            $response = empty($postFields) ? $req->post($url) : $req->post($url, $postFields);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => (bool) ($data['success'] ?? false),
                    'embedding' => $data['embedding'] ?? null,
                    'blur_score' => $data['blur_score'] ?? null,
                    'clahe_enhanced' => (bool) ($data['clahe_enhanced'] ?? false),
                    'centroid_merged' => (bool) ($data['centroid_merged'] ?? false),
                    'message' => $data['message'] ?? null,
                ];
            }

            Log::error("Face Engine extractVector failed: HTTP {$response->status()} - " . $response->body());
            return [
                'success' => false,
                'message' => 'Layanan ekstraksi wajah merespons galat (HTTP ' . $response->status() . ').'
            ];
        } catch (\Throwable $e) {
            Log::error("Face Engine connection error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke Face Recognition Engine (Python Service). Pastikan microservice aktif di ' . self::getBaseUrl()
            ];
        }
    }

    /**
     * Compare 1:1 check-in selfie photo with master face embedding
     *
     * @param array|string $masterEmbedding
     * @param UploadedFile|string $checkinPhoto
     * @param UserFaceData|null $faceRecord Optional record for lazy 128d->512d upgrade & adaptive centroid update
     * @return array ['success' => bool, 'is_match' => bool, 'similarity' => float, 'message' => ?string]
     */
    public static function compareFace($masterEmbedding, $checkinPhoto, ?UserFaceData $faceRecord = null): array
    {
        $url = self::getBaseUrl() . '/compare-face';

        try {
            $unpacked = self::unpackEmbedding($masterEmbedding);
            $vec512 = $unpacked['v512'];

            // Lazy auto-upgrade: if user only had a 128-d kiosk vector but has sample_photo_path on disk, extract 512-d now
            if (empty($vec512) && $faceRecord && !empty($faceRecord->sample_photo_path)) {
                $sampleFullPath = storage_path('app/public/' . ltrim($faceRecord->sample_photo_path, '/'));
                if (file_exists($sampleFullPath)) {
                    $lazyExtract = self::extractVector($sampleFullPath);
                    if (!empty($lazyExtract['success']) && !empty($lazyExtract['embedding'])) {
                        $vec512 = $lazyExtract['embedding'];
                        $faceRecord->face_embedding = self::packEmbedding($vec512, $unpacked['v128']);
                        $faceRecord->save();
                    }
                }
            }

            if (empty($vec512)) {
                return [
                    'success' => false,
                    'is_match' => false,
                    'similarity' => 0.0,
                    'message' => 'Vektor wajah 512-d belum tersedia. Silakan daftarkan ulang Face ID Anda.'
                ];
            }

            $embeddingJson = json_encode($vec512);
            $req = Http::timeout(30);

            if ($checkinPhoto instanceof UploadedFile) {
                $req = $req->attach('checkin_photo', file_get_contents($checkinPhoto->getRealPath()), $checkinPhoto->getClientOriginalName());
            } elseif (is_string($checkinPhoto) && file_exists($checkinPhoto)) {
                $req = $req->attach('checkin_photo', file_get_contents($checkinPhoto), basename($checkinPhoto));
            } else {
                return [
                    'success' => false,
                    'is_match' => false,
                    'similarity' => 0.0,
                    'message' => 'File foto selfie check-in tidak valid.'
                ];
            }

            $response = $req->attach('master_embedding_json', $embeddingJson)->post($url);

            if ($response->successful()) {
                $data = $response->json();
                $similarity = (float) ($data['similarity'] ?? 0.0);
                $isMatch = (bool) ($data['is_match'] ?? ($similarity >= self::getThreshold()));
                $simPercent = (float) ($data['similarity_percent'] ?? round($similarity * 100, 2));
                $thresholdPercent = round(self::getThreshold() * 100, 1);

                // Adaptive Multi-Template Centroid Update (max once every 12 hours when match confidence >= 82%)
                if ($isMatch && $similarity >= 0.82 && $faceRecord && !empty($data['updated_embedding']) && is_array($data['updated_embedding'])) {
                    if (!$faceRecord->updated_at || $faceRecord->updated_at->diffInHours(now()) >= 12) {
                        $faceRecord->face_embedding = self::packEmbedding($data['updated_embedding'], $unpacked['v128']);
                        $faceRecord->save();
                    }
                }

                if ($isMatch) {
                    Log::info("[Face Engine] Pencocokan Wajah COCOK: Kemiripan {$simPercent}% (Skor: {$similarity}, Min Threshold: {$thresholdPercent}%)");
                } else {
                    Log::warning("[Face Engine] Pencocokan Wajah TIDAK COCOK: Kemiripan {$simPercent}% (Skor: {$similarity}, Min Threshold: {$thresholdPercent}%)");
                }

                return [
                    'success' => (bool) ($data['success'] ?? false),
                    'is_match' => $isMatch,
                    'similarity' => $similarity,
                    'similarity_percent' => $simPercent,
                    'message' => $data['message'] ?? null,
                ];
            }

            Log::error("Face Engine compareFace failed: HTTP {$response->status()} - " . $response->body());
            return [
                'success' => false,
                'is_match' => false,
                'similarity' => 0.0,
                'message' => 'Layanan perbandingan wajah gagal memproses data (HTTP ' . $response->status() . ').'
            ];
        } catch (\Throwable $e) {
            Log::error("Face Engine compare connection error: " . $e->getMessage());
            return [
                'success' => false,
                'is_match' => false,
                'similarity' => 0.0,
                'message' => 'Gagal menghubungi server Face Engine: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fast 1:N Face Identification via Python NumPy Matrix Multiplication (/identify-face).
     *
     * @param UploadedFile|string $photo
     * @param iterable $candidates Collection/array of UserFaceData models
     * @param float|null $threshold
     * @return array ['success' => bool, 'is_match' => bool, 'matched_record' => ?UserFaceData, 'similarity' => float, 'message' => ?string]
     */
    public static function identifyFace($photo, iterable $candidates, ?float $threshold = null): array
    {
        $activeThreshold = $threshold ?? self::getThreshold();
        $payloadCandidates = [];
        $recordMap = [];

        foreach ($candidates as $cand) {
            if (!($cand instanceof UserFaceData)) {
                continue;
            }
            $unpacked = self::unpackEmbedding($cand->face_embedding);
            $vec512 = $unpacked['v512'];

            // Lazy upgrade if a kiosk-registered record only has 128-d but has sample photo on disk
            if (empty($vec512) && !empty($cand->sample_photo_path)) {
                $sampleFullPath = storage_path('app/public/' . ltrim($cand->sample_photo_path, '/'));
                if (file_exists($sampleFullPath)) {
                    $lazyExtract = self::extractVector($sampleFullPath);
                    if (!empty($lazyExtract['success']) && !empty($lazyExtract['embedding'])) {
                        $vec512 = $lazyExtract['embedding'];
                        $cand->face_embedding = self::packEmbedding($vec512, $unpacked['v128']);
                        $cand->save();
                    }
                }
            }

            if (empty($vec512) || count($vec512) !== 512) {
                continue;
            }

            $personId = $cand->siswa_id ?: $cand->guru_id;
            $personType = $cand->siswa_id ? 'siswa' : 'guru';
            if (!$personId) {
                continue;
            }

            $payloadCandidates[] = [
                'user_face_id' => $cand->id,
                'id' => $personId,
                'type' => $personType,
                'embedding' => $vec512,
            ];
            $recordMap[$cand->id] = [
                'record' => $cand,
                'v128' => $unpacked['v128'],
                'v512' => $vec512,
            ];
        }

        if (empty($payloadCandidates)) {
            return [
                'success' => false,
                'is_match' => false,
                'no_512_candidates' => true,
                'engine_offline' => false,
                'matched_record' => null,
                'similarity' => 0.0,
                'message' => 'Belum ada kandidat wajah 512-d terdaftar di sekolah ini.'
            ];
        }

        $url = self::getBaseUrl() . '/identify-face';

        try {
            $req = Http::connectTimeout(2)->timeout(12);
            if ($photo instanceof UploadedFile) {
                $req = $req->attach('photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName());
            } elseif (is_string($photo) && file_exists($photo)) {
                $req = $req->attach('photo', file_get_contents($photo), basename($photo));
            } else {
                return [
                    'success' => false,
                    'is_match' => false,
                    'engine_offline' => false,
                    'matched_record' => null,
                    'similarity' => 0.0,
                    'message' => 'File foto tidak valid.'
                ];
            }

            $response = $req
                ->attach('candidates_json', json_encode($payloadCandidates))
                ->attach('threshold', (string) $activeThreshold)
                ->post($url);

            if ($response->successful()) {
                $data = $response->json();
                $isMatch = (bool) ($data['is_match'] ?? false);
                $similarity = (float) ($data['similarity'] ?? 0.0);
                $matchedFaceId = $data['user_face_id'] ?? null;

                $matchedRecord = null;
                if ($isMatch && $matchedFaceId && isset($recordMap[$matchedFaceId])) {
                    $matchedRecord = $recordMap[$matchedFaceId]['record'];

                    // Adaptive Centroid Update if high confidence (>= 0.82) and not updated in last 12h
                    if ($similarity >= 0.82 && !empty($data['updated_embedding']) && is_array($data['updated_embedding'])) {
                        if (!$matchedRecord->updated_at || $matchedRecord->updated_at->diffInHours(now()) >= 12) {
                            $matchedRecord->face_embedding = self::packEmbedding(
                                $data['updated_embedding'],
                                $recordMap[$matchedFaceId]['v128']
                            );
                            $matchedRecord->save();
                        }
                    }
                }

                return [
                    'success' => (bool) ($data['success'] ?? false),
                    'is_match' => $isMatch && $matchedRecord !== null,
                    'engine_offline' => false,
                    'matched_record' => $matchedRecord,
                    'similarity' => $similarity,
                    'similarity_percent' => (float) ($data['similarity_percent'] ?? round($similarity * 100, 2)),
                    'message' => $data['message'] ?? null,
                ];
            }

            // Fallback to /extract-face-vector + PHP dot product if Python service hasn't been restarted with /identify-face yet
            if ($response->status() === 404) {
                $ext = self::extractVector($photo);
                if (empty($ext['success']) || empty($ext['embedding'])) {
                    return [
                        'success' => false,
                        'is_match' => false,
                        'engine_offline' => false,
                        'matched_record' => null,
                        'similarity' => 0.0,
                        'message' => $ext['message'] ?? 'Wajah tidak terdeteksi.'
                    ];
                }
                $snapVec = self::normalizeVector($ext['embedding']);
                $bestScore = 0.0;
                $bestRecord = null;
                foreach ($recordMap as $item) {
                    $sim = self::cosineSimilarity($snapVec, $item['v512']);
                    if ($sim > $bestScore) {
                        $bestScore = $sim;
                        $bestRecord = $item['record'];
                    }
                }
                $isMatch = $bestRecord !== null && $bestScore >= $activeThreshold;
                return [
                    'success' => true,
                    'is_match' => $isMatch,
                    'engine_offline' => false,
                    'matched_record' => $isMatch ? $bestRecord : null,
                    'similarity' => round($bestScore, 4),
                    'similarity_percent' => round($bestScore * 100, 2),
                    'message' => $isMatch ? null : 'Wajah tidak cocok dalam database.',
                ];
            }

            return [
                'success' => false,
                'is_match' => false,
                'engine_offline' => in_array($response->status(), [502, 503, 504], true),
                'matched_record' => null,
                'similarity' => 0.0,
                'message' => 'Layanan identifikasi wajah merespons galat (HTTP ' . $response->status() . ').'
            ];
        } catch (\Throwable $e) {
            Log::error("Face Engine identifyFace error: " . $e->getMessage());
            return [
                'success' => false,
                'is_match' => false,
                'engine_offline' => true,
                'matched_record' => null,
                'similarity' => 0.0,
                'message' => 'Gagal menghubungi Face Engine: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate Cosine Similarity between two vector arrays
     *
     * @param array $vecA
     * @param array $vecB
     * @return float
     */
    public static function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $len = min(count($vecA), count($vecB));
        for ($i = 0; $i < $len; $i++) {
            $dot += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }
        return ($normA > 0 && $normB > 0) ? ($dot / (sqrt($normA) * sqrt($normB))) : 0.0;
    }
}
