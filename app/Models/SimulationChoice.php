<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $node_id
 * @property string $label
 * @property int|null $next_node_id
 * @property int $points
 * @property bool $is_best
 * @property string|null $feedback
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SimulationChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'node_id',
        'label',
        'label_kh',
        'next_node_id',
        'points',
        'is_best',
        'feedback',
        'feedback_kh',
    ];

    protected $casts = [
        'node_id' => 'integer',
        'next_node_id' => 'integer',
        'points' => 'integer',
        'is_best' => 'boolean',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(SimulationNode::class, 'node_id');
    }

    public function nextNode(): BelongsTo
    {
        return $this->belongsTo(SimulationNode::class, 'next_node_id');
    }
}
