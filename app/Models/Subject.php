<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $major_id
 * @property int|null $department_id
 * @property string $code
 * @property string $name
 * @property string|null $name_kh
 * @property int $credits
 * @property string|null $prerequisite
 * @property string $difficulty
 * @property string|null $description
 * @property string $status
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'major_id',
        'department_id',
        'code',
        'name',
        'name_kh',
        'credits',
        'prerequisite',
        'difficulty',
        'description',
        'status',
        'is_active',
    ];

    protected $casts = [
        'credits'   => 'integer',
        'is_active' => 'boolean',
    ];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
