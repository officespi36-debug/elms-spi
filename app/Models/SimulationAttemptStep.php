<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attempt_id
 * @property int $node_id
 * @property int $choice_id
 * @property int $points
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class SimulationAttemptStep extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'attempt_id',
        'node_id',
        'choice_id',
        'points',
        'created_at',
    ];

    protected $casts = [
        'attempt_id' => 'integer',
        'node_id' => 'integer',
        'choice_id' => 'integer',
        'points' => 'integer',
        'created_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(SimulationAttempt::class, 'attempt_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(SimulationNode::class, 'node_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(SimulationChoice::class, 'choice_id');
    }
}
