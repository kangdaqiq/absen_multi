<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageQueue extends Model
{
    protected $table = 'message_queues';
    public $timestamps = true;
    public bool $bypass_last_seen = false;
    protected $fillable = ['school_id', 'phone_number', 'message', 'status', 'priority', 'scheduled_at', 'attempts', 'last_error', 'retry_count', 'created_at', 'updated_at'];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];
    
    protected static function booted()
    {
        static::creating(function ($messageQueue) {
            // Clean up attributes if bypass_last_seen was dynamically set into attributes
            unset($messageQueue->attributes['bypass_last_seen']);

            // Check Last Seen rule (default 3 days / 72 hours) unless explicitly bypassed
            if (empty($messageQueue->bypass_last_seen)) {
                $expiryDays = (int) (\App\Models\Setting::where('setting_key', 'last_seen_expiry_days')->value('setting_value') ?: 3);
                $expiryHours = $expiryDays * 24;
                if (!static::isRecipientWithinLastSeen($messageQueue->phone_number, $expiryHours)) {
                    \Illuminate\Support\Facades\Log::warning("Skipped WA message queue for {$messageQueue->phone_number}: last_seen is null or older than {$expiryHours} hours.");
                    return false;
                }
            }

            if ($messageQueue->school_id && !empty($messageQueue->message)) {
                $school = \App\Models\School::find($messageQueue->school_id);
                if ($school && !empty($school->name)) {
                    $signature = "*" . trim($school->name) . "*";
                    if (!str_contains($messageQueue->message, $signature)) {
                        $messageQueue->message = rtrim($messageQueue->message) . "\n\n" . $signature;
                    }
                }
            }
        });
    }

    /**
     * Cek apakah penerima WA (Guru, Siswa, atau Ortu) aktif dalam $hours jam terakhir (Last Seen).
     */
    public static function isRecipientWithinLastSeen(?string $phone, int $hours = 72): bool
    {
        if (empty($phone)) {
            return false;
        }

        // Jika nomor adalah WA Group (@g.us), jangan batasi last seen
        if (str_contains($phone, '@g.us')) {
            return true;
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) {
            return false;
        }

        $variants = [$clean];
        if (str_starts_with($clean, '62')) {
            $variants[] = '0' . substr($clean, 2);
        } elseif (str_starts_with($clean, '0')) {
            $variants[] = '62' . substr($clean, 1);
        }

        // 1. Cek Guru
        $guru = \App\Models\Guru::whereIn('no_wa', $variants)->first();
        if ($guru) {
            return $guru->isWithinLastSeen($hours);
        }

        // 2. Cek Siswa (sebagai nomor siswa)
        $siswa = \App\Models\Siswa::whereIn('no_wa', $variants)->first();
        if ($siswa) {
            return $siswa->isSiswaWithinLastSeen($hours);
        }

        // 3. Cek Siswa (sebagai nomor orang tua)
        $ortuSiswa = \App\Models\Siswa::whereIn('wa_ortu', $variants)->first();
        if ($ortuSiswa) {
            return $ortuSiswa->isOrtuWithinLastSeen($hours);
        }

        // Jika tidak terdaftar di tabel Guru/Siswa/Ortu (misal admin/sistem), izinkan pengiriman.
        return true;
    }
    
    /**
     * Mutator to automatically format phone number into WhatsApp JID.
     */
    public function setPhoneNumberAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['phone_number'] = $value;
            return;
        }

        // If it already contains '@', assume it is already a JID (e.g. @s.whatsapp.net or @g.us)
        // Since we are changing to pure numeric format, we will STRIP @s.whatsapp.net if it exists
        if (str_ends_with($value, '@s.whatsapp.net')) {
            $value = str_replace('@s.whatsapp.net', '', $value);
        }
        if (str_contains($value, '@')) {
            $this->attributes['phone_number'] = $value;
            return;
        }

        // Clean non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $value);

        if (empty($phone)) {
            $this->attributes['phone_number'] = $value;
            return;
        }

        // Standardize Indonesian format: 08xx -> 628xx
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            // Assume it is already without prefix if not starting with 62 or 0
            $phone = '62' . $phone;
        }

        // Append WhatsApp domain
        // $this->attributes['phone_number'] = $phone . '@s.whatsapp.net';
        $this->attributes['phone_number'] = $phone;
    }
}
