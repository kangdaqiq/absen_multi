<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PwaBrandingController extends Controller
{
    /**
     * Ambil konfigurasi branding PWA berdasarkan school (id, code, atau domain).
     * GET /api/pwa/branding?school=...
     */
    public function branding(Request $request)
    {
        $school = $this->resolveSchool($request);
        $data = $this->buildBrandingPayload($school, $request);

        return response()->json([
            'success' => true,
            'data' => $data,
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Generate Web App Manifest (.webmanifest) dinamis per sekolah.
     * GET /api/pwa/manifest?school=...&origin=...
     */
    public function manifest(Request $request)
    {
        $school = $this->resolveSchool($request);
        $branding = $this->buildBrandingPayload($school, $request);

        $origin = rtrim((string) $request->query('origin', ''), '/');
        $schoolIdentifier = $branding['school_code'] ?: $branding['school_id'] ?: 'default';

        $startUrl = $origin
            ? ($origin . '/?school=' . urlencode((string) $schoolIdentifier))
            : ('/?school=' . urlencode((string) $schoolIdentifier));

        $scope = $origin ? ($origin . '/') : '/';

        $manifest = [
            'id' => 'jagat-absen-' . Str::slug((string) $schoolIdentifier),
            'name' => $branding['app_name'],
            'short_name' => $branding['short_name'],
            'description' => $branding['description'],
            'theme_color' => '#1E40AF',
            'background_color' => '#F8FAFC',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'scope' => $scope,
            'start_url' => $startUrl,
            'icons' => [
                [
                    'src' => $branding['icon_192_url'],
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $branding['icon_512_url'],
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 200)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    /**
     * Sajikan icon PNG ukuran {size}x{size} (192 / 512) untuk Favicon, Web App Manifest & Apple Touch Icon.
     * GET /api/pwa/icon/{school}/{size}.png
     */
    public function icon(Request $request, string $schoolParam, int $size = 192)
    {
        $size = in_array($size, [64, 180, 192, 512]) ? $size : 192;
        $request->merge(['school' => $schoolParam]);
        $school = $this->resolveSchool($request);
        $branding = $this->buildBrandingPayload($school, $request);

        $rawBytes = null;
        $diskPath = $this->resolveLogoDiskPath($school);

        if ($diskPath && file_exists($diskPath)) {
            $rawBytes = @file_get_contents($diskPath);
        } elseif (!empty($branding['logo_url']) && Str::startsWith($branding['logo_url'], ['http://', 'https://']) && !Str::endsWith($branding['logo_url'], '.svg')) {
            try {
                $resp = Http::timeout(10)->get($branding['logo_url']);
                if ($resp->successful()) {
                    $rawBytes = $resp->body();
                }
            } catch (\Throwable $e) {
                // Ignore and fallback below
            }
        }

        // Jika bytes gambar tersedia dan ekstensi GD aktif, resize ke ukuran persegi PNG transparan
        if ($rawBytes && function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor')) {
            $srcImg = @imagecreatefromstring($rawBytes);
            if ($srcImg !== false) {
                $srcW = imagesx($srcImg);
                $srcH = imagesy($srcImg);

                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                // Gunakan background transparan penuh agar PNG transparan tidak berubah warna
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);
                imagealphablending($canvas, true);

                $padding = (int) round($size * 0.04);
                $avail = $size - ($padding * 2);
                $scale = min($avail / max($srcW, 1), $avail / max($srcH, 1));
                $dstW = (int) round($srcW * $scale);
                $dstH = (int) round($srcH * $scale);
                $dstX = (int) round(($size - $dstW) / 2);
                $dstY = (int) round(($size - $dstH) / 2);

                imagecopyresampled($canvas, $srcImg, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
                imagesavealpha($canvas, true);

                ob_start();
                imagepng($canvas);
                $pngBytes = ob_get_clean();

                imagedestroy($srcImg);
                imagedestroy($canvas);

                return response($pngBytes, 200)
                    ->header('Content-Type', 'image/png')
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Cache-Control', 'public, max-age=300');
            }
        }

        // Jika file ada di disk namun bukan raster/GD tidak aktif, kirim langsung
        if ($diskPath && file_exists($diskPath)) {
            $mime = mime_content_type($diskPath) ?: 'image/png';
            return response(file_get_contents($diskPath), 200)
                ->header('Content-Type', $mime)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Cache-Control', 'public, max-age=300');
        }

        // Fallback: redirect ke logo URL sekolah (misal di R2 / eksternal) atau avatar inisial PNG
        if (!empty($branding['logo_url']) && Str::startsWith($branding['logo_url'], ['http://', 'https://']) && !Str::endsWith($branding['logo_url'], '.svg')) {
            return redirect()->away($branding['logo_url']);
        }

        $safeName = urlencode($branding['short_name'] ?: 'JA');
        return redirect()->away("https://ui-avatars.com/api/?name={$safeName}&background=1E40AF&color=fff&size={$size}&bold=true&format=png");
    }

    /**
     * Bangun struktur data branding untuk sebuah sekolah.
     */
    public function buildBrandingPayload(?School $school, ?Request $request = null): array
    {
        $schoolId = $school?->id ?? 0;

        $settings = Setting::whereIn('school_id', array_unique([0, $schoolId]))
            ->whereIn('setting_key', [
                'nama_sekolah',
                'logo_filename',
                'pwa_app_name',
                'pwa_short_name',
                'pwa_description',
                'pwa_logo_filename',
            ])
            ->get()
            ->sortBy('school_id') // school_id spesifik menimpa school_id = 0
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $schoolName = $school?->name
            ?: ($settings['nama_sekolah'] ?? 'Jagat Absen');

        $appName = !empty($settings['pwa_app_name'])
            ? $settings['pwa_app_name']
            : $schoolName;

        $shortName = !empty($settings['pwa_short_name'])
            ? $settings['pwa_short_name']
            : Str::limit($appName, 15, '');

        $description = !empty($settings['pwa_description'])
            ? $settings['pwa_description']
            : "Sistem Presensi & Jurnal Mengajar Digital {$schoolName}";

        $logoUrl = $this->resolvePwaLogoUrl($school, $settings);
        $schoolCode = $school?->code ?: ($schoolId > 0 ? (string) $schoolId : 'default');
        $versionHash = substr(md5((string) $logoUrl . '|' . $appName), 0, 10);

        return [
            'school_id' => $schoolId > 0 ? $schoolId : null,
            'school_code' => $schoolCode,
            'school_name' => $schoolName,
            'app_name' => $appName,
            'short_name' => $shortName,
            'description' => $description,
            'logo_url' => $logoUrl,
            'icon_192_url' => url("/api/pwa/icon/{$schoolCode}/192.png") . "?v={$versionHash}",
            'icon_512_url' => url("/api/pwa/icon/{$schoolCode}/512.png") . "?v={$versionHash}",
            'manifest_url' => url('/api/pwa/manifest?school=' . urlencode((string) $schoolCode) . "&v={$versionHash}"),
        ];
    }

    /**
     * Cari School dari parameter ?school= (bisa berupa ID, kode sekolah, domain, atau slug nama).
     */
    private function resolveSchool(Request $request): ?School
    {
        $param = trim((string) ($request->query('school') ?? $request->query('kode') ?? ''));

        if ($param !== '' && $param !== 'default') {
            if (is_numeric($param)) {
                $byId = School::find((int) $param);
                if ($byId) {
                    return $byId;
                }
            }

            $byCodeOrDomain = School::where('code', $param)
                ->orWhere('domain', $param)
                ->first();
            if ($byCodeOrDomain) {
                return $byCodeOrDomain;
            }

            // Coba cocokkan berdasarkan slug nama sekolah
            $allSchools = School::all();
            foreach ($allSchools as $s) {
                if (Str::slug($s->name) === Str::slug($param)) {
                    return $s;
                }
            }
        }

        // Jika mode self_hosted atau hanya ada 1 sekolah aktif, gunakan sekolah pertama
        if (config('app.mode') === 'self_hosted' || School::count() === 1) {
            return School::first();
        }

        return null;
    }

    private function resolvePwaLogoUrl(?School $school, array $settings): string
    {
        $customPwaLogo = $settings['pwa_logo_filename'] ?? null;
        if (!empty($customPwaLogo)) {
            if (Str::startsWith($customPwaLogo, ['http://', 'https://'])) {
                return $customPwaLogo;
            }
            $clean = ltrim(str_replace(['\\', '/'], '/', $customPwaLogo), '/');
            return asset('storage/' . $clean);
        }

        if ($school) {
            $schoolLogo = $school->logo_url;
            if ($schoolLogo && !Str::endsWith($schoolLogo, ['img/logo.png', '/logo.png'])) {
                return $schoolLogo;
            }
        }

        $logo = $settings['logo_filename'] ?? null;
        if (!empty($logo) && $logo !== 'logo.png') {
            if (Str::startsWith($logo, ['http://', 'https://'])) {
                return $logo;
            }
            $clean = ltrim(str_replace(['\\', '/'], '/', $logo), '/');
            if (file_exists(public_path('storage/' . $clean))) {
                return asset('storage/' . $clean);
            }
            if (file_exists(public_path('images/logo/' . $clean))) {
                return asset('images/logo/' . $clean);
            }
            return asset('storage/' . $clean);
        }

        return asset('images/logo/logo-icon.svg');
    }

    private function resolveLogoDiskPath(?School $school): ?string
    {
        $schoolId = $school?->id ?? 0;

        $pwaLogo = Setting::where('school_id', $schoolId)->where('setting_key', 'pwa_logo_filename')->value('setting_value')
            ?? Setting::where('school_id', 0)->where('setting_key', 'pwa_logo_filename')->value('setting_value');

        $candidates = array_filter([
            $pwaLogo,
            $school?->logo,
            Setting::where('school_id', $schoolId)->where('setting_key', 'logo_filename')->value('setting_value'),
            Setting::where('school_id', 0)->where('setting_key', 'logo_filename')->value('setting_value'),
        ], fn($val) => !empty($val) && $val !== 'logo.png');

        foreach ($candidates as $rel) {
            if (Str::startsWith($rel, ['http://', 'https://'])) {
                continue;
            }
            $clean = ltrim(str_replace(['\\', '/'], '/', $rel), '/');
            $paths = [
                storage_path('app/public/' . $clean),
                public_path('storage/' . $clean),
                public_path('images/logo/' . $clean),
                public_path($clean),
            ];
            foreach ($paths as $p) {
                if (file_exists($p) && is_file($p)) {
                    return $p;
                }
            }
        }

        return null;
    }
}
