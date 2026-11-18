<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRecommendation extends Model
{
    protected $fillable = [
        'user_id', 'lesson_id', 'type', 'reason', 'ai_payload', 'is_dismissed'
    ];

    protected $casts = [
        'ai_payload'   => 'array',
        'is_dismissed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
