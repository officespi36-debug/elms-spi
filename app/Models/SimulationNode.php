<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $simulation_id
 * @property int|null $topic_id
 * @property string|null $title
 * @property string $body
 * @property string|null $media_path
 * @property bool $is_end
 * @property string|null $feedback
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SimulationNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'topic_id',
        'title',
        'body',
        'media_path',
        'is_end',
        'feedback',
    ];

    protected $casts = [
        'is_end' => 'boolean',
        'topic_id' => 'integer',
        'simulation_id' => 'integer',
    ];

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(SimulationChoice::class, 'node_id');
    }
}
