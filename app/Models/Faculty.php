<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $name_kh
 * @property string|null $code
 * @property string|null $dean
 * @property string|null $email
 * @property int|null $est_year
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Faculty extends Model
{
    protected $fillable = ['name', 'name_kh', 'code', 'dean', 'email', 'est_year', 'description', 'is_active'];

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function majors()
    {
        return $this->hasManyThrough(Major::class, Department::class);
    }
}
