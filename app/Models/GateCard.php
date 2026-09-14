<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateCard extends Model
{
    use HasFactory;

    protected $table = 'gate_cards';

    protected static function booted()
    {
        static::deleting(function ($gateCard) {
            $fingerIds = $gateCard->fingerprints()->pluck('finger_id')->toArray();
            if ($gateCard->id_finger && !in_array($gateCard->id_finger, $fingerIds)) {
                $fingerIds[] = (int)$gateCard->id_finger;
            }
            if (!empty($fingerIds) && $gateCard->school_id) {
                Device::queueFingerDeletion($gateCard->school_id, $fingerIds);
            }
            $gateCard->fingerprints()->delete();
        });
    }

    protected $fillable = [
        'school_id',
        'guru_id',
        'uid_rfid',
        'name',
        'enroll_status',
        'id_finger',
        'enroll_finger_status',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function fingerprints()
    {
        return $this->hasMany(GateCardFingerprint::class, 'gate_card_id');
    }
}
