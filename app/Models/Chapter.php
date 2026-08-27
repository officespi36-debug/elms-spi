<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $module_id
 * @property int|null $chapter_number
 * @property string $title
 * @property string|null $kh_title
 * @property int|null $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Module|null $module
 */
class Chapter extends Model
{
    protected $fillable = [
        'module_id', 'chapter_number', 'title', 'kh_title', 'order'
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}
