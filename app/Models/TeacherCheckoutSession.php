<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherCheckoutSession extends Model
{
    protected $table = 'teacher_checkout_sessions';
    public $timestamps = false; // Managed manually in legacy or just created_at
    protected $fillable = ['teacher_id', 'teacher_name', 'uid_rfid', 'status', 'expires_at', 'created_at'];

    public function teacher()
    {
        return $this->belongsTo(Guru::class, 'teacher_id');
    }
}
