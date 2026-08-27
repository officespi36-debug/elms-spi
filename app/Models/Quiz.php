<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $course_id
 * @property int|null $module_id
 * @property string $title
 * @property string|null $type
 * @property int|null $time_limit_minutes
 * @property float|null $passing_score
 * @property int|null $max_attempts
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Course|null $course
 */
class Quiz extends Model
{
    protected $fillable = [
        'course_id', 'module_id', 'title', 'type', 'time_limit_minutes',
        'passing_score', 'max_attempts', 'status'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
