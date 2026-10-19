<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JurnalPelajaran;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncDokumentasiMediaTypes extends Command
{
    protected $signature = 'dok:sync-media-types';
    protected $description = 'Sinkronisasi tipe berkas (foto/video) dokumentasi KBM dari Storage Server';

    public function handle()
    {
        $this->info('Memulai sinkronisasi tipe berkas dokumentasi KBM...');

        $jurnals = JurnalPelajaran::whereNotNull('dokumentasi')
            ->where('dokumentasi', '!=', '[]')
            ->where('dokumentasi', '!=', '""')
            ->where('dokumentasi', '!=', '')
            ->get();

        $storageUrl = rtrim(config('services.storage_server.url') ?: 'https://storage.jagattech.my.id', '/');
        $storageKey = config('services.storage_server.key') ?: '812194c6c53ee6557e088b897f5ec08abafb49a8fa5e656f5acf75f70a93145f';

        $totalUpdated = 0;

        foreach ($jurnals as $jurnal) {
            $raw = $jurnal->dokumentasi;
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }
            if (!is_array($raw) || empty($raw)) {
                continue;
            }

            $changed = false;
            $newDocs = [];

            foreach ($raw as $item) {
                $itemUrl = is_array($item) ? ($item['url'] ?? ($item['view_url'] ?? '')) : (string) $item;
                $fileId = is_array($item) ? ($item['id'] ?? null) : null;

                if (empty($fileId) && preg_match('#/api/v1/files/([^/?]+)#', $itemUrl, $m)) {
                    $fileId = $m[1];
                }

                $isVideo = false;
                $origName = null;
                $mimeType = null;
                $thumbUrl = null;

                if (!empty($fileId)) {
                    try {
                        $res = Http::timeout(5)
                            ->withHeaders([
                                'X-API-Key' => $storageKey,
                                'Authorization' => 'Bearer ' . $storageKey,
                            ])
                            ->get("{$storageUrl}/api/v1/files/{$fileId}", [
                                'api_key' => $storageKey,
                            ]);

                        if ($res->successful()) {
                            $fData = $res->json('data') ?? [];
                            $fileType = strtolower($fData['file_type'] ?? '');
                            $mimeType = strtolower($fData['mime_type'] ?? '');
                            $origName = $fData['original_name'] ?? null;
                            $ext = strtolower(pathinfo($origName ?? '', PATHINFO_EXTENSION));

                            $isVideo = ($fileType === 'video')
                                || str_starts_with($mimeType, 'video/')
                                || in_array($ext, ['mp4', 'mov', 'webm', '3gp', 'mkv', 'avi'])
                                || str_contains(strtolower($origName ?? ''), 'vid');

                            $thumbUrl = $fData['thumbnail_url'] ?? null;
                        }
                    } catch (\Exception $e) {
                        Log::warning("[SyncDok] Failed fetching metadata for {$fileId}: " . $e->getMessage());
                    }
                }

                if (!$isVideo) {
                    $nameCheck = is_array($item) ? ($item['name'] ?? ($item['original_name'] ?? '')) : basename($itemUrl);
                    $isVideo = str_contains(strtolower($nameCheck), 'vid')
                        || str_contains(strtolower($itemUrl), 'video')
                        || str_contains(strtolower($itemUrl), 'vid');
                }

                $cleanName = $origName ?: (is_array($item) ? ($item['name'] ?? null) : null);
                if (empty($cleanName) || in_array(strtolower($cleanName), ['view', 'download', 'file', 'thumbnail'])) {
                    $cleanName = $isVideo ? 'Video Pembelajaran' : 'Foto Kegiatan';
                }

                $newItem = [
                    'id'            => $fileId,
                    'url'           => $itemUrl,
                    'view_url'      => $itemUrl,
                    'download_url'  => is_array($item) ? ($item['download_url'] ?? $itemUrl) : $itemUrl,
                    'thumbnail_url' => $thumbUrl ?: (is_array($item) ? ($item['thumbnail_url'] ?? null) : null),
                    'type'          => $isVideo ? 'video' : 'image',
                    'is_video'      => $isVideo,
                    'is_image'      => !$isVideo,
                    'name'          => $cleanName,
                    'mime_type'     => $mimeType ?: (is_array($item) ? ($item['mime_type'] ?? null) : null),
                ];

                $newDocs[] = $newItem;
                $changed = true;
            }

            if ($changed) {
                $jurnal->dokumentasi = $newDocs;
                $jurnal->save();
                $totalUpdated++;
            }
        }

        $this->info("Selesai! {$totalUpdated} sesi jurnal berhasil disinkronkan.");
        return 0;
    }
}
