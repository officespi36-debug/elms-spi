<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property int|null $lesson_id
 * @property string $title
 * @property string|null $title_kh
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Course|null $course
 * @property-read Lesson|null $lesson
 */
class Topic extends Model
{
    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'title_kh',
        'position',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function masteries(): HasMany
    {
        return $this->hasMany(TopicMastery::class);
    }
}
