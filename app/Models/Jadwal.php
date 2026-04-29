<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';
    public $timestamps = true;
    protected $fillable = ['hari', 'index_hari', 'jam_masuk', 'jam_pulang', 'awal_absen_masuk', 'akhir_absen_masuk', 'akhir_absen_pulang', 'is_active', 'school_id'];
}
