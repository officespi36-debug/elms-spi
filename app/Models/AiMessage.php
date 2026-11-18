<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'body',
        'provider',
        'model',
        'latency_ms',
        'tokens',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
        'tokens'     => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(AiFeedback::class, 'message_id');
    }
}
