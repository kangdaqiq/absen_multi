<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruFingerprint extends Model
{
    use HasFactory;
    
    protected $table = 'guru_fingerprints';
    
    protected $fillable = [
        'guru_id',
        'device_id',
        'finger_id'
    ];
    
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
    
    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
