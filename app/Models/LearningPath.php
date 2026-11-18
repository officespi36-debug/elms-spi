<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property int|null $major_id
 * @property string $title
 * @property string|null $title_kh
 * @property string|null $description
 * @property string|null $description_kh
 * @property int $target_months
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LearningPath extends Model
{
    protected $fillable = [
        'slug',
        'major_id',
        'title',
        'title_kh',
        'description',
        'description_kh',
        'target_months',
        'is_published',
    ];

    protected $casts = [
        'is_published'  => 'boolean',
        'target_months' => 'integer',
    ];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function stages()
    {
        return $this->hasMany(PathStage::class, 'path_id')->orderBy('position')->orderBy('id');
    }

    public function userGoals()
    {
        return $this->hasMany(UserGoal::class, 'path_id');
    }
}
