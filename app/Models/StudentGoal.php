<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGoal extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'title_km',
        'is_completed',
        'target_route',
        'goal_date',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'goal_date'    => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
