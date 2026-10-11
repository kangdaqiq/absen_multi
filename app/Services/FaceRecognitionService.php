<?php

namespace App\Services;

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
     * Get similarity threshold (default 75% / 0.75)
     */
    public static function getThreshold(): float
    {
        return (float) (config('services.face_engine.threshold') ?: env('FACE_SIMILARITY_THRESHOLD', 0.75));
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
     * Extract 512-dimension vector embedding from master photo
     *
     * @param UploadedFile|string $photo
     * @return array ['success' => bool, 'embedding' => ?array, 'message' => ?string]
     */
    public static function extractVector($photo): array
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

            $response = $req->post($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => (bool) ($data['success'] ?? false),
                    'embedding' => $data['embedding'] ?? null,
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
     * Compare check-in selfie photo with master face embedding
     *
     * @param array|string $masterEmbedding
     * @param UploadedFile|string $checkinPhoto
     * @return array ['success' => bool, 'is_match' => bool, 'similarity' => float, 'message' => ?string]
     */
    public static function compareFace($masterEmbedding, $checkinPhoto): array
    {
        $url = self::getBaseUrl() . '/compare-face';

        try {
            $embeddingJson = is_array($masterEmbedding) ? json_encode($masterEmbedding) : (string) $masterEmbedding;

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

            $response = $req->asMultipart()->post($url, [
                [
                    'name' => 'master_embedding_json',
                    'contents' => $embeddingJson
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $similarity = (float) ($data['similarity'] ?? 0.0);
                $isMatch = (bool) ($data['is_match'] ?? ($similarity >= self::getThreshold()));

                return [
                    'success' => (bool) ($data['success'] ?? false),
                    'is_match' => $isMatch,
                    'similarity' => $similarity,
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
}
