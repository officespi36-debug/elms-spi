<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $path_id
 * @property int $position
 * @property string $title
 * @property string|null $title_kh
 * @property string|null $description
 * @property string|null $description_kh
 */
class PathStage extends Model
{
    protected $fillable = [
        'path_id',
        'position',
        'title',
        'title_kh',
        'description',
        'description_kh',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function path()
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }

    public function stageCourses()
    {
        return $this->hasMany(PathStageCourse::class, 'stage_id')->orderBy('position')->orderBy('id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'path_stage_courses', 'stage_id', 'course_id')
            ->withPivot(['position', 'is_required'])
            ->orderBy('path_stage_courses.position')
            ->orderBy('path_stage_courses.id');
    }
}
