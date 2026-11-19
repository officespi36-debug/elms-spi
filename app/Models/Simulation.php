<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property int|null $lesson_id
 * @property int $teacher_id
 * @property string $title
 * @property string|null $title_kh
 * @property string $skill
 * @property string $kind
 * @property string|null $instructions
 * @property int $estimated_minutes
 * @property bool $is_graded
 * @property int $pass_score
 * @property int $max_attempts
 * @property int|null $start_node_id
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Simulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'teacher_id',
        'title',
        'title_kh',
        'skill',
        'kind',
        'instructions',
        'estimated_minutes',
        'is_graded',
        'pass_score',
        'max_attempts',
        'start_node_id',
        'is_published',
    ];

    protected $casts = [
        'estimated_minutes' => 'integer',
        'is_graded' => 'boolean',
        'pass_score' => 'integer',
        'max_attempts' => 'integer',
        'start_node_id' => 'integer',
        'is_published' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(SimulationNode::class);
    }

    public function startNode(): BelongsTo
    {
        return $this->belongsTo(SimulationNode::class, 'start_node_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(SimulationAttempt::class);
    }
}
