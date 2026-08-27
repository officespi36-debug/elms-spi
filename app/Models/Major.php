<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property string|null $name_kh
 * @property string|null $code
 * @property float|null $price_per_subject
 * @property string|null $duration
 * @property string|null $degree_level
 * @property int|null $credits
 * @property string|null $language
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Department|null $department
 */
class Major extends Model
{
    protected $fillable = ['department_id', 'name', 'name_kh', 'code', 'price_per_subject', 'duration', 'degree_level', 'credits', 'language', 'description', 'is_active'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function enrollments()
    {
        return $this->hasManyThrough(Enrollment::class, Course::class);
    }
}
