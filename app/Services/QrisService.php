<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * QrisService - Qiospay Official Dynamic QRIS & API Service
 * Compatible with reactmore/qiospay-sdk
 */
class QrisService
{
    /**
     * Default base static QRIS string (Indonesian National QRIS template).
     * Used when no custom merchant string is configured in .env.
     */
    public const DEFAULT_BASE_QRIS = '00020101021126570011ID.CO.QRIS.WWW011893600002000000000002082950941560303UMI51440014ID.LINKAJA.WWW02159360099900000105204549953033605802ID5908QIOSPAY6007JAKARTA61051011063040E0A';

    /**
     * QRIS service fee codes according to ASPI / Qiospay standard.
     */
    public const FEE_CODE_PERSEN = '55020357';
    public const FEE_CODE_RUPIAH = '55020256';

    /**
     * Get Base QRIS string from configuration.
     */
    public static function getBaseQrisString(): string
    {
        $fromConfig = null;
        if (function_exists('config')) {
            try {
                $fromConfig = config('services.qiospay.qris_string') ?: config('services.qiospay.base_qris_string');
            } catch (\Throwable $e) {
                // Laravel container might not be booted yet
            }
        }

        return $fromConfig
            ?: (function_exists('env') ? (env('QIOSPAY_QRIS_STRING') ?: env('QRIS_BASE_STRING')) : null)
            ?: self::DEFAULT_BASE_QRIS;
    }

    /**
     * Generate dynamic QRIS matching official Qiospay SDK method createQris().
     *
     * @param array $params [
     *      'amount'      => (int/float) nominal amount,
     *      'service_fee' => (bool) include fee or not,
     *      'fee_type'    => 'persen' | 'rupiah',
     *      'fee_value'   => (float/int) fee amount/percentage (e.g. 0.7),
     *      'base_qris'   => (string|null) custom static QRIS,
     * ]
     * @return array ['qris_string' => string, 'qris_image' => string]
     */
    public static function createQris(array $params = []): array
    {
        $amount     = $params['amount'] ?? null;
        $serviceFee = $params['service_fee'] ?? false;
        $feeType    = $params['fee_type'] ?? 'persen';
        $feeValue   = $params['fee_value'] ?? 0.7;
        $baseQris   = $params['base_qris'] ?? self::getBaseQrisString();

        $dynamicQris = self::generateDynamicQris($amount, $baseQris, $serviceFee, $feeType, $feeValue);
        $imageUrl    = self::getQrCodeUrl($dynamicQris);

        return [
            'status'      => 'success',
            'qris_string' => $dynamicQris,
            'qris_image'  => $imageUrl,
            'amount'      => $amount,
        ];
    }

    /**
     * Generate an EMVCo-compliant Dynamic QRIS payload string with the exact transaction amount.
     *
     * @param float|int|null $amount Transaction amount (e.g. 50783)
     * @param string|null $baseQris Optional static QRIS string to transform
     * @param bool $serviceFee Whether to attach fee tag
     * @param string $feeType 'persen' or 'rupiah'
     * @param float|int $feeValue Fee rate or amount
     * @return string Dynamic QRIS string with embedded nominal and valid CRC16
     */
    public static function generateDynamicQris(
        float|int|null $amount,
        ?string $baseQris = null,
        bool $serviceFee = false,
        string $feeType = 'persen',
        float|int $feeValue = 0.7
    ): string {
        $qris = trim($baseQris ?: self::getBaseQrisString());

        if (empty($qris)) {
            $qris = self::DEFAULT_BASE_QRIS;
        }

        if (!$amount) {
            return $qris;
        }

        // 1. Calculate Tax / Service Fee tag if enabled
        $taxTag = '';
        if ($serviceFee && $feeValue > 0) {
            $feeStr = trim((string) $feeValue);
            $feeCode = ($feeType === 'rupiah') ? self::FEE_CODE_RUPIAH : self::FEE_CODE_PERSEN;
            $taxTag = $feeCode . sprintf('%02d', strlen($feeStr)) . $feeStr;
        }

        // 2. Strip existing CRC (last 4 hex chars at end of string)
        $qrBase = preg_replace('/6304[A-Fa-f0-9]{4}$/i', '', $qris);
        if (str_ends_with($qrBase, '6304')) {
            $qrBase = substr($qrBase, 0, -4);
        }

        // 3. Switch initiation method from static (010211) to dynamic (010212)
        $qrBase = str_replace('010211', '010212', $qrBase);

        // 4. Format Amount Tag (Tag 54)
        $amountInt = (int) round($amount);
        $amountStr = (string) $amountInt;
        $nominalTag = '54' . sprintf('%02d', strlen($amountStr)) . $amountStr;

        // 5. Insert nominal & fee tags before Tag 58 (Country Code: 5802ID)
        if (str_contains($qrBase, '5802ID')) {
            $parts = explode('5802ID', $qrBase, 2);
            $insert = $nominalTag . ($taxTag ? $taxTag : '') . '5802ID';
            $qrFinal = trim($parts[0]) . $insert . trim($parts[1]);
        } elseif (str_contains($qrBase, '5802id')) {
            $parts = explode('5802id', $qrBase, 2);
            $insert = $nominalTag . ($taxTag ? $taxTag : '') . '5802id';
            $qrFinal = trim($parts[0]) . $insert . trim($parts[1]);
        } else {
            $qrFinal = $qrBase . $nominalTag . $taxTag;
        }

        // 6. Append Tag 6304 and calculate CRC16
        $qrFinal .= '6304';
        $crc = self::calculateCrc16($qrFinal);

        return $qrFinal . $crc;
    }

    /**
     * Calculate 16-bit CRC checksum (CRC-16/CCITT-FALSE: poly 0x1021, init 0xFFFF).
     */
    public static function calculateCrc16(string $payload): string
    {
        $crc = 0xFFFF;
        $len = strlen($payload);

        for ($c = 0; $c < $len; $c++) {
            $crc ^= ord($payload[$c]) << 8;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Retrieve QRIS mutations from Qiospay API (matching official QiosPay SDK).
     * Endpoint: GET api/mutasi/qris/{merchantCode}/{apiKey}
     */
    public static function getMutation(array $filters = []): array
    {
        $merchantCode = config('services.qiospay.merchant_code', env('QIOSPAY_MERCHANT_CODE'));
        $apiKey       = config('services.qiospay.api_key', env('QIOSPAY_API_KEY'));

        if (empty($merchantCode) || empty($apiKey)) {
            return [
                'status'  => 'error',
                'message' => 'Qiospay merchantCode atau apiKey belum dikonfigurasi di .env',
                'data'    => []
            ];
        }

        try {
            $baseUrl = rtrim(config('services.qiospay.base_url', env('QIOSPAY_BASE_URL', 'https://qiospay.id')), '/');
            $response = Http::timeout(15)->get("{$baseUrl}/api/mutasi/qris/{$merchantCode}/{$apiKey}", $filters);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return [
                'status'  => 'error',
                'message' => 'Gagal mengambil mutasi QRIS dari server Qiospay. HTTP: ' . $response->status(),
                'data'    => []
            ];
        } catch (\Throwable $e) {
            Log::error('Qiospay getMutation error: ' . $e->getMessage());
            return [
                'status'  => 'error',
                'message' => 'Terjadi kesalahan koneksi ke server Qiospay: ' . $e->getMessage(),
                'data'    => []
            ];
        }
    }

    /**
     * Get QR Code image URL for the generated QRIS payload.
     */
    public static function getQrCodeUrl(string $payload, int $size = 300): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($payload);
    }
}
