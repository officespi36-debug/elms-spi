<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonCommentLike extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
    ];

    public function comment()
    {
        return $this->belongsTo(LessonComment::class, 'comment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
