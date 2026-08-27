<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $faculty_id
 * @property string $name
 * @property string|null $name_kh
 * @property string|null $code
 * @property string|null $head
 * @property string|null $email
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Faculty|null $faculty
 */
class Department extends Model
{
    protected $fillable = ['faculty_id', 'name', 'name_kh', 'code', 'head', 'email', 'description', 'is_active'];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function majors()
    {
        return $this->hasMany(Major::class);
    }
}
