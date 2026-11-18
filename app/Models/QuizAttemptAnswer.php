<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attempt_id
 * @property int $question_id
 * @property array|null $selected_answer
 * @property bool $is_correct
 * @property float $points_earned
 * @property \Illuminate\Support\Carbon|null $answered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read QuizAttempt|null $attempt
 * @property-read Question|null $question
 */
class QuizAttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_answer',
        'is_correct',
        'points_earned',
        'answered_at',
    ];

    protected $casts = [
        'selected_answer' => 'array',
        'is_correct'      => 'boolean',
        'points_earned'   => 'decimal:2',
        'answered_at'     => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
