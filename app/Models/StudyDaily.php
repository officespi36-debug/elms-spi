<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyDaily extends Model
{
    protected $table = 'study_daily';

    protected $fillable = [
        'user_id',
        'study_date',
        'kind',
        'seconds',
    ];

    protected $casts = [
        'study_date' => 'date:Y-m-d',
        'seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
