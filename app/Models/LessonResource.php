<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonResource extends Model
{
    protected $fillable = [
        'lesson_id',
        'title',
        'disk_path',
        'original_name',
        'mime',
        'size_bytes',
        'sort_order',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['formatted_size', 'file_extension'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes ?: 0;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getFileExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->original_name ?: $this->title, PATHINFO_EXTENSION) ?: 'FILE');
    }
}
