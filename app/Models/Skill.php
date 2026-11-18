<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $name_kh
 */
class Skill extends Model
{
    protected $fillable = [
        'name',
        'name_kh',
    ];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_skills');
    }
}
