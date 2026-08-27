<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $quiz_id
 * @property string $type
 * @property string $question
 * @property array|null $options
 * @property array|string|null $correct_answer
 * @property float|null $points
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Quiz|null $quiz
 */
class Question extends Model
{
    protected $fillable = [
        'quiz_id', 'type', 'question', 'options', 'correct_answer', 'points'
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}
