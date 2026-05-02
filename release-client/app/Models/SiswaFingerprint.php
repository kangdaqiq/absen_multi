<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiswaFingerprint extends Model
{
    use HasFactory;
    
    protected $table = 'siswa_fingerprints';
    
    protected $fillable = [
        'student_id',
        'device_id',
        'finger_id'
    ];
    
    public function student()
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }
    
    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
