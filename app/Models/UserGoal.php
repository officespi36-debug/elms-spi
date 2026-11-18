<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $path_id
 * @property int $target_months
 * @property int $weekly_hours
 * @property \Illuminate\Support\Carbon|null $started_at
 */
class UserGoal extends Model
{
    protected $fillable = [
        'user_id',
        'path_id',
        'target_months',
        'weekly_hours',
        'started_at',
    ];

    protected $casts = [
        'target_months' => 'integer',
        'weekly_hours'  => 'integer',
        'started_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function path()
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }
}
