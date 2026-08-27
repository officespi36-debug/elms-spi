<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $quiz_id
 * @property array|null $answers
 * @property float|null $score
 * @property bool $passed
 * @property int|null $attempt_number
 * @property string|null $client_uuid
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Quiz|null $quiz
 */
class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id', 'quiz_id', 'answers', 'score', 'passed',
        'attempt_number', 'client_uuid', 'started_at', 'submitted_at'
    ];

    protected $casts = [
        'answers' => 'array',
        'passed' => 'boolean',
        'score' => 'decimal:2',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
