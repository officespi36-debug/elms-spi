<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lesson_id',
        'user_id',
        'parent_id',
        'body',
        'video_seconds',
        'is_pinned',
        'edited_at',
        'is_answered',
        'answered_comment_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_answered' => 'boolean',
        'video_seconds' => 'integer',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['relative_time', 'video_timestamp_formatted'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'avatar', 'role');
    }

    public function parent()
    {
        return $this->belongsTo(LessonComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(LessonComment::class, 'parent_id')
            ->with(['user:id,name,avatar,role'])
            ->withCount('likes')
            ->oldest();
    }

    public function likes()
    {
        return $this->hasMany(LessonCommentLike::class, 'comment_id');
    }

    public function answeredComment()
    {
        return $this->belongsTo(LessonComment::class, 'answered_comment_id');
    }

    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : '';
    }

    public function getVideoTimestampFormattedAttribute(): ?string
    {
        if ($this->video_seconds === null || $this->video_seconds === false) {
            return null;
        }

        $sec = (int) $this->video_seconds;
        $hours = floor($sec / 3600);
        $minutes = floor(($sec % 3600) / 60);
        $seconds = $sec % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
