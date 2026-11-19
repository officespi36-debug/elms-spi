<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $simulation_id
 * @property int $user_id
 * @property int $attempt_no
 * @property int|null $current_node_id
 * @property int $score
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SimulationAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'user_id',
        'attempt_no',
        'current_node_id',
        'score',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'simulation_id' => 'integer',
        'user_id' => 'integer',
        'attempt_no' => 'integer',
        'current_node_id' => 'integer',
        'score' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentNode(): BelongsTo
    {
        return $this->belongsTo(SimulationNode::class, 'current_node_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SimulationAttemptStep::class, 'attempt_id');
    }
}
