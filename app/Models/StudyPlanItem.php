<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $day_index
 * @property int $topic_id
 * @property int $minutes
 * @property string $kind
 * @property \Illuminate\Support\Carbon|null $done_at
 * @property int|null $student_goal_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read StudyPlan|null $plan
 * @property-read Topic|null $topic
 * @property-read StudentGoal|null $studentGoal
 */
class StudyPlanItem extends Model
{
    protected $fillable = [
        'plan_id',
        'day_index',
        'topic_id',
        'minutes',
        'kind',
        'done_at',
        'student_goal_id',
    ];

    protected $casts = [
        'day_index' => 'integer',
        'minutes'   => 'integer',
        'done_at'   => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'plan_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function studentGoal(): BelongsTo
    {
        return $this->belongsTo(StudentGoal::class, 'student_goal_id');
    }
}
