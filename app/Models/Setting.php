<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    public $timestamps = false;
    protected $fillable = ['setting_key', 'setting_value', 'school_id'];
    protected $primaryKey = 'setting_key'; // Assuming key is primary or unique
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Ambil nilai setting dengan hirarki:
     * 1. Setting spesifik sekolah (jika school_id disediakan)
     * 2. Setting global (school_id = 0)
     * 3. Fallback tanpa filter school_id
     * 4. Nilai default
     */
    public static function getVal(string $key, $default = null, ?int $schoolId = null)
    {
        if ($schoolId !== null && $schoolId > 0) {
            $val = static::where('school_id', $schoolId)->where('setting_key', $key)->value('setting_value');
            if ($val !== null && $val !== '') {
                return $val;
            }
        }

        $globalVal = static::where('school_id', 0)->where('setting_key', $key)->value('setting_value');
        if ($globalVal !== null && $globalVal !== '') {
            return $globalVal;
        }

        $fallbackVal = static::where('setting_key', $key)->value('setting_value');
        return ($fallbackVal !== null && $fallbackVal !== '') ? $fallbackVal : $default;
    }
}
