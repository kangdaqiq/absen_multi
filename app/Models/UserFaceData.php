<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFaceData extends Model
{
    use HasFactory;

    protected $table = 'user_face_data';

    protected $fillable = [
        'user_id',
        'siswa_id',
        'guru_id',
        'face_embedding',
        'sample_photo_path',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    protected $appends = [
        'sample_photo_url',
    ];

    public function getSamplePhotoUrlAttribute(): ?string
    {
        if (empty($this->sample_photo_path)) {
            return null;
        }
        if (\Illuminate\Support\Str::startsWith($this->sample_photo_path, ['http://', 'https://'])) {
            return $this->sample_photo_path;
        }
        return asset('storage/' . ltrim($this->sample_photo_path, '/'));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
}
