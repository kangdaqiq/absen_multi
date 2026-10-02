<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--client= : Override client name for self-hosted backup} {--selfhosted : Force self-hosted mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database to storage/app/backups and Cloudflare R2';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        $isSelfHosted = (config('app.mode') === 'self_hosted') || $this->option('selfhosted');
        $keepDays = $isSelfHosted ? 2 : 7;

        if ($isSelfHosted) {
            $clientName = $this->getClientName();
            $clientSlug = Str::slug($clientName, '-');
            if (empty($clientSlug)) {
                $clientSlug = 'client';
            }
            // Format file: client-name_tgl-bckup (contoh: sman1-jakarta_01-10-2026.sql)
            $filename = "{$clientSlug}_" . Carbon::now()->format('d-m-Y') . ".sql";
            $cloudFolder = 'Client';
            $this->info("Self-hosted mode detected. Client: '{$clientSlug}', Retention: {$keepDays} days");
        } else {
            $clientSlug = null;
            $filename = "backup-" . Carbon::now()->format('Y-m-d-H-i-s') . ".sql";
            $cloudFolder = 'backups';
            $this->info("Hosted mode detected. Retention: {$keepDays} days");
        }

        $path = storage_path("app/backups");

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = "$path/$filename";
        
        // Database Config
        $host = config('database.connections.mysql.host');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $database = config('database.connections.mysql.database');

        // Path to mysqldump
        if (PHP_OS_FAMILY === 'Windows') {
             $mysqldumpPath = 'c:\xampp\mysql\bin\mysqldump.exe';
             if (!file_exists($mysqldumpPath)) {
                 $mysqldumpPath = 'd:\xampp\mysql\bin\mysqldump.exe'; // Check D: drive XAMPP
                 if (!file_exists($mysqldumpPath)) {
                     $mysqldumpPath = 'e:\xampp\mysql\bin\mysqldump.exe'; // Check E: drive XAMPP
                     if (!file_exists($mysqldumpPath)) {
                         $mysqldumpPath = 'mysqldump'; // Try global if not found
                     }
                 }
             }
        } else {
             $mysqldumpPath = '/usr/bin/mysqldump'; // Typical Linux path
             if (!file_exists($mysqldumpPath)) {
                 $mysqldumpPath = 'mysqldump'; // Try global path
             }
        }
        
        // Command Construction
        // Note: Password argument -p must be attached immediately to value without space
        $passwordArg = !empty($password) ? "--password=\"$password\"" : "";
        $errorLogPath = "$path/dump_error.log";
        
        // Write SQL stdout to $filePath and stderr to separate error log file (never mix stderr into .sql file)
        // Note: --no-tablespaces is required for MySQL 8.0+ when the database user lacks global PROCESS privilege
        $command = "\"$mysqldumpPath\" --user=\"$username\" $passwordArg --host=\"$host\" --no-tablespaces \"$database\" > \"$filePath\" 2> \"$errorLogPath\"";

        $this->info("Executing mysqldump backup to {$filename}...");
        
        $output = [];
        $returnVar = null;
        exec($command, $output, $returnVar);

        // Sanitize: ensure no stray mysqldump warning or error header was written into the SQL file
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if (str_contains($content, 'mysqldump:')) {
                // Strip lines or prefixes starting with mysqldump: up to the next newline or SQL statement
                $content = preg_replace('/^mysqldump:[^\r\n]*?(\r?\n|(?=--|\/\*))/m', '', $content);
                file_put_contents($filePath, $content);
            }
        }

        // Clean up error log if empty or successfully finished
        if (file_exists($errorLogPath) && $returnVar === 0) {
            @unlink($errorLogPath);
        }

        if ($returnVar === 0) {
            $this->info("Backup successful: $filename");
            
            // Upload to Cloudflare R2 if configured
            $r2Endpoint = config('filesystems.disks.r2.endpoint') ?: env('CLOUDFLARE_R2_ENDPOINT');
            $r2Key = config('filesystems.disks.r2.key') ?: env('CLOUDFLARE_R2_ACCESS_KEY_ID');
            $r2Bucket = config('filesystems.disks.r2.bucket') ?: env('CLOUDFLARE_R2_BUCKET');

            if (!empty($r2Endpoint) && !empty($r2Key) && !empty($r2Bucket)) {
                $cloudPath = "{$cloudFolder}/{$filename}";
                $this->info("Uploading backup to Cloudflare R2 ({$cloudPath})...");
                try {
                    $stream = fopen($filePath, 'r');
                    $uploaded = Storage::disk('r2')->put($cloudPath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    if ($uploaded !== false) {
                        $this->info("Backup successfully uploaded to Cloudflare R2: {$cloudPath}");
                    } else {
                        $this->warn("Upload to Cloudflare R2 returned false. Check permissions or credentials.");
                    }
                    
                    // Clean old backups in Cloudflare R2
                    $this->cleanOldCloudBackups($cloudFolder, $keepDays, $clientSlug);
                } catch (\Exception $e) {
                    $this->error("Failed to upload to Cloudflare R2: " . $e->getMessage());
                }
            } else {
                $this->line("Cloudflare R2 is not configured. Skipping cloud upload.");
            }

            // Clean old backups locally
            $this->cleanOldBackups($path, $keepDays);
            
        } else {
            $errorDetails = file_exists($errorLogPath) ? file_get_contents($errorLogPath) : '';
            $this->error("Backup failed with exit code $returnVar. Error: $errorDetails");
        }
    }

    /**
     * Resolve the client name for self-hosted installations.
     */
    private function getClientName(): string
    {
        // 1. Explicit CLI option
        if ($this->option('client')) {
            return trim($this->option('client'));
        }

        // 2. Explicit config / env
        $configured = config('app.client_name') ?: env('CLIENT_NAME');
        if (!empty($configured)) {
            return trim($configured);
        }

        // 3. License validation result
        try {
            $licenseService = app(\App\Services\LicenseService::class);
            $licenseData = $licenseService->validate();
            if (!empty($licenseData['client_name']) && $licenseData['client_name'] !== 'Hosted') {
                return trim($licenseData['client_name']);
            }
        } catch (\Throwable $e) {
            // Ignore license check error
        }

        // 4. Setting nama_sekolah
        try {
            $schoolName = \App\Models\Setting::where('setting_key', 'nama_sekolah')
                ->where('setting_value', '!=', '')
                ->value('setting_value');
            if (!empty($schoolName)) {
                return trim($schoolName);
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // 5. Active School model
        try {
            $school = \App\Models\School::where('is_active', true)->first();
            if ($school && !empty($school->name)) {
                return trim($school->name);
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // 6. Fallback
        return config('app.name', 'client');
    }

    /**
     * Clean old backups locally from storage/app/backups.
     */
    private function cleanOldBackups(string $path, int $keepDays)
    {
        $files = glob("$path/*.sql");
        $now = time();
        $today = Carbon::today();
        $cutoffDate = $today->copy()->subDays($keepDays - 1);
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $basename = basename($file);
            $shouldDelete = false;

            // 1. Try parsing date in format: *_DD-MM-YYYY.sql
            if (preg_match('/_(\d{2}-\d{2}-\d{4})\.sql$/i', $basename, $matches)) {
                try {
                    $fileDate = Carbon::createFromFormat('d-m-Y', $matches[1])->startOfDay();
                    if ($fileDate->lt($cutoffDate)) {
                        $shouldDelete = true;
                    }
                } catch (\Throwable $e) {
                    // fallback to filemtime
                }
            } elseif (preg_match('/backup-(\d{4}-\d{2}-\d{2})/i', $basename, $matches)) {
                // Format: backup-YYYY-MM-DD-*.sql
                try {
                    $fileDate = Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
                    if ($fileDate->lt($cutoffDate)) {
                        $shouldDelete = true;
                    }
                } catch (\Throwable $e) {
                    // fallback to filemtime
                }
            }

            // 2. Fallback to file modification time
            if (!$shouldDelete && ($now - filemtime($file) >= 60 * 60 * 24 * $keepDays)) {
                $shouldDelete = true;
            }

            if ($shouldDelete) {
                unlink($file);
                $this->info("Deleted old local backup: " . $basename);
            }
        }
    }

    /**
     * Clean old backups from Cloudflare R2 storage.
     *
     * @param string $folder Folder in bucket (e.g. 'Client' or 'backups')
     * @param int $keepDays Number of days to keep
     * @param string|null $clientSlug If set, only clean this client's files in 'Client' folder
     */
    private function cleanOldCloudBackups(string $folder, int $keepDays, ?string $clientSlug = null)
    {
        $this->info("Cleaning old backups from Cloudflare R2 in '{$folder}' (older than {$keepDays} days)...");
        try {
            $disk = Storage::disk('r2');
            $files = $disk->files($folder);
            $now = time();
            $today = Carbon::today();
            $cutoffDate = $today->copy()->subDays($keepDays - 1);

            foreach ($files as $file) {
                $basename = basename($file);
                $matches = [];

                // Determine pattern match
                if ($folder === 'Client') {
                    // Self-hosted client backup:
                    // If clientSlug is provided, only clean this client's backups
                    if ($clientSlug) {
                        $pattern = '/^' . preg_quote($clientSlug, '/') . '_(\d{2}-\d{2}-\d{4})\.sql$/i';
                        if (!preg_match($pattern, $basename, $matches)) {
                            // If doesn't match this client's exact prefix, skip to protect other clients' data
                            continue;
                        }
                    } else {
                        // General client pattern
                        if (!preg_match('/^.+_(\d{2}-\d{2}-\d{4})\.sql$/i', $basename, $matches)) {
                            continue;
                        }
                    }
                } else {
                    // Hosted backup (default: backups/backup-*.sql)
                    if (!preg_match('/^backup-.*\.sql$/i', $basename)) {
                        continue;
                    }
                }

                $shouldDelete = false;

                // 1. Try parsing date from filename
                if (isset($matches[1])) {
                    try {
                        $fileDate = Carbon::createFromFormat('d-m-Y', $matches[1])->startOfDay();
                        if ($fileDate->lt($cutoffDate)) {
                            $shouldDelete = true;
                        }
                    } catch (\Throwable $e) {
                        // ignore and fall back to lastModified
                    }
                } elseif (preg_match('/backup-(\d{4}-\d{2}-\d{2})/i', $basename, $dateMatches)) {
                    try {
                        $fileDate = Carbon::createFromFormat('Y-m-d', $dateMatches[1])->startOfDay();
                        if ($fileDate->lt($cutoffDate)) {
                            $shouldDelete = true;
                        }
                    } catch (\Throwable $e) {
                        // ignore and fall back to lastModified
                    }
                }

                // 2. Fallback to S3 lastModified
                if (!$shouldDelete) {
                    try {
                        $lastModified = $disk->lastModified($file);
                        if ($now - $lastModified >= 60 * 60 * 24 * $keepDays) {
                            $shouldDelete = true;
                        }
                    } catch (\Throwable $e) {
                        $this->warn("Could not retrieve lastModified for cloud file {$basename}: " . $e->getMessage());
                    }
                }

                if ($shouldDelete) {
                    try {
                        $disk->delete($file);
                        $this->info("Deleted old cloud backup: {$file}");
                    } catch (\Throwable $e) {
                        $this->error("Failed to delete cloud file {$file}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to clean old cloud backups: " . $e->getMessage());
        }
    }
}

