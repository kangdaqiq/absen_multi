<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GateCardFingerprint extends Model
{
    use HasFactory;

    protected $table = 'gate_card_fingerprints';

    protected $fillable = [
        'gate_card_id',
        'device_id',
        'finger_id',
        'template_data'
    ];

    public function gateCard()
    {
        return $this->belongsTo(GateCard::class, 'gate_card_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
