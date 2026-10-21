<?php

namespace App\Exceptions;

use Exception;

class StorageQuotaExceededException extends Exception
{
    public int $usedBytes;
    public int $quotaBytes;
    public int $attemptedBytes;

    public function __construct(
        string $message = 'Kapasitas penyimpanan sekolah telah penuh.',
        int $usedBytes = 0,
        int $quotaBytes = 0,
        int $attemptedBytes = 0,
        int $code = 413,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->usedBytes = $usedBytes;
        $this->quotaBytes = $quotaBytes;
        $this->attemptedBytes = $attemptedBytes;
    }
}
