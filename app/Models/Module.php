<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property string|null $kh_title
 * @property string|null $description
 * @property string|null $kh_description
 * @property string|null $learning_objectives
 * @property string|null $estimated_duration
 * @property int|null $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Course|null $course
 */
class Module extends Model
{
    protected $fillable = [
        'course_id', 'title', 'kh_title', 'description', 'kh_description',
        'learning_objectives', 'estimated_duration', 'order'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}
