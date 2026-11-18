<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $topic_id
 * @property int $answered_count
 * @property int $correct_count
 * @property float $score
 * @property \Illuminate\Support\Carbon|null $last_attempt_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Topic|null $topic
 */
class TopicMastery extends Model
{
    protected $table = 'topic_mastery';

    protected $fillable = [
        'user_id',
        'topic_id',
        'answered_count',
        'correct_count',
        'score',
        'last_attempt_at',
    ];

    protected $casts = [
        'answered_count'  => 'integer',
        'correct_count'   => 'integer',
        'score'           => 'decimal:2',
        'last_attempt_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
