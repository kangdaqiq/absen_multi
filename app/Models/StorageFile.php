<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageFile extends Model
{
    use HasFactory;

    protected $table = 'storage_files';

    protected $fillable = [
        'school_id',
        'license_id',
        'disk',
        'path',
        'url',
        'original_name',
        'size_bytes',
        'mime_type',
        'category',
        'uploader_id',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function license()
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function getSizeHumanAttribute(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
