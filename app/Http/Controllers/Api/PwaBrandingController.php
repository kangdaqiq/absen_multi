<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Setting;
use Illuminate\Http\Request;
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

        $icon192 = url("/api/pwa/icon/{$schoolIdentifier}/192.png");
        $icon512 = url("/api/pwa/icon/{$schoolIdentifier}/512.png");

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
                    'src' => $icon192,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $icon512,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 200)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Sajikan icon PNG ukuran {size}x{size} (192 / 512) untuk Web App Manifest & Apple Touch Icon.
     * GET /api/pwa/icon/{school}/{size}.png
     */
    public function icon(Request $request, string $schoolParam, int $size = 192)
    {
        $size = in_array($size, [180, 192, 512]) ? $size : 192;
        $request->merge(['school' => $schoolParam]);
        $school = $this->resolveSchool($request);

        $diskPath = $this->resolveLogoDiskPath($school);

        // Jika ekstensi GD tersedia dan file gambar raster ada di disk, resize ke ukuran persegi PNG
        if ($diskPath && file_exists($diskPath) && function_exists('imagecreatetruecolor')) {
            $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
            $srcImg = match ($ext) {
                'png' => @imagecreatefrompng($diskPath),
                'jpg', 'jpeg' => @imagecreatefromjpeg($diskPath),
                'gif' => @imagecreatefromgif($diskPath),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($diskPath) : false,
                default => false,
            };

            if ($srcImg) {
                $srcW = imagesx($srcImg);
                $srcH = imagesy($srcImg);

                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                // Background putih bersih dengan padding halus agar logo sekolah proporsional
                $white = imagecolorallocatealpha($canvas, 255, 255, 255, 0);
                imagefilledrectangle($canvas, 0, 0, $size, $size, $white);
                imagealphablending($canvas, true);

                $padding = (int) round($size * 0.08);
                $avail = $size - ($padding * 2);
                $scale = min($avail / max($srcW, 1), $avail / max($srcH, 1));
                $dstW = (int) round($srcW * $scale);
                $dstH = (int) round($srcH * $scale);
                $dstX = (int) round(($size - $dstW) / 2);
                $dstY = (int) round(($size - $dstH) / 2);

                imagecopyresampled($canvas, $srcImg, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

                ob_start();
                imagepng($canvas);
                $pngBytes = ob_get_clean();

                imagedestroy($srcImg);
                imagedestroy($canvas);

                return response($pngBytes, 200)
                    ->header('Content-Type', 'image/png')
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Cache-Control', 'public, max-age=3600');
            }
        }

        // Jika file ada di disk namun bukan raster/GD tidak aktif, kirim langsung
        if ($diskPath && file_exists($diskPath)) {
            $mime = mime_content_type($diskPath) ?: 'image/png';
            return response(file_get_contents($diskPath), 200)
                ->header('Content-Type', $mime)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Cache-Control', 'public, max-age=3600');
        }

        // Fallback: redirect ke logo URL sekolah (misal di R2 / eksternal) atau avatar inisial PNG
        $branding = $this->buildBrandingPayload($school, $request);
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

        return [
            'school_id' => $schoolId > 0 ? $schoolId : null,
            'school_code' => $schoolCode,
            'school_name' => $schoolName,
            'app_name' => $appName,
            'short_name' => $shortName,
            'description' => $description,
            'logo_url' => $logoUrl,
            'icon_192_url' => url("/api/pwa/icon/{$schoolCode}/192.png"),
            'icon_512_url' => url("/api/pwa/icon/{$schoolCode}/512.png"),
            'manifest_url' => url('/api/pwa/manifest?school=' . urlencode((string) $schoolCode)),
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
            return $school->logo_url ?: asset('img/logo.png');
        }

        $logo = $settings['logo_filename'] ?? null;
        if (!empty($logo)) {
            if (Str::startsWith($logo, ['http://', 'https://'])) {
                return $logo;
            }
            $clean = ltrim(str_replace(['\\', '/'], '/', $logo), '/');
            if (file_exists(public_path('storage/' . $clean))) {
                return asset('storage/' . $clean);
            }
            if (file_exists(public_path('img/' . $clean))) {
                return asset('img/' . $clean);
            }
            return asset('storage/' . $clean);
        }

        return asset('img/logo.png');
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
        ]);

        foreach ($candidates as $rel) {
            if (Str::startsWith($rel, ['http://', 'https://'])) {
                continue;
            }
            $clean = ltrim(str_replace(['\\', '/'], '/', $rel), '/');
            $paths = [
                storage_path('app/public/' . $clean),
                public_path('storage/' . $clean),
                public_path('img/' . $clean),
                public_path($clean),
            ];
            foreach ($paths as $p) {
                if (file_exists($p) && is_file($p)) {
                    return $p;
                }
            }
        }

        $defaultPng = public_path('img/logo.png');
        return file_exists($defaultPng) ? $defaultPng : null;
    }
}
