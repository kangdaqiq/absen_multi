<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    protected $table = 'jurusan';
    
    protected $fillable = ['nama_jurusan', 'school_id'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function kelas()
    {
        return $this->hasMany(Kelas::class);
    }
}
