<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $stage_id
 * @property int $course_id
 * @property int $position
 * @property bool $is_required
 */
class PathStageCourse extends Model
{
    protected $fillable = [
        'stage_id',
        'course_id',
        'position',
        'is_required',
    ];

    protected $casts = [
        'position'    => 'integer',
        'is_required' => 'boolean',
    ];

    public function stage()
    {
        return $this->belongsTo(PathStage::class, 'stage_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
