<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    // Enable timestamps as table has updated_at/created_at
    public $timestamps = true;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password_hash',
        'role',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Overrides for Auth
     */
    public function getAuthPassword() {
        return $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // Map 'name' to 'full_name' accessor if needed
    public function getNameAttribute() {
        return $this->full_name;
    }

    public function student()
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }
}
