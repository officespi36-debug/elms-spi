<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $module_id
 * @property int|null $chapter_id
 * @property int|null $course_id
 * @property string $title
 * @property string|null $kh_title
 * @property string|null $type
 * @property string|null $file_path
 * @property string|null $file_url
 * @property string|null $video_url
 * @property string|null $content
 * @property string|null $ai_summary
 * @property string|null $thumbnail
 * @property int|null $duration_seconds
 * @property int|null $order
 * @property bool $is_free_preview
 * @property bool $downloadable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Module|null $module
 * @property-read Chapter|null $chapter
 * @property-read Course|null $course
 */
class Lesson extends Model
{
    protected $fillable = [
        'module_id', 'chapter_id', 'course_id', 'title', 'kh_title', 'type', 'file_path', 'file_url',
        'video_url', 'content', 'ai_summary', 'thumbnail', 'duration_seconds', 'order', 'is_free_preview', 'downloadable'
    ];

    protected $casts = [
        'is_free_preview' => 'boolean',
        'downloadable' => 'boolean',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function videos()
    {
        return $this->hasMany(CourseVideo::class);
    }

    public function materials()
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function aiContents()
    {
        return $this->hasMany(AiGeneratedContent::class);
    }

    public function labIntegrations()
    {
        return $this->hasMany(LabIntegration::class);
    }
}
