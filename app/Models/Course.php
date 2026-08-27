<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $major_id
 * @property int|null $teacher_id
 * @property string $title
 * @property string|null $code
 * @property string|null $description
 * @property string|null $learning_mode
 * @property bool $is_paid
 * @property float|null $price
 * @property string $status
 * @property string|null $thumbnail
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $rejection_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Major|null $major
 * @property-read User|null $teacher
 */
class Course extends Model
{
    protected $fillable = [
        'major_id', 'teacher_id', 'title', 'code', 'description',
        'learning_mode', 'is_paid', 'price', 'status', 'thumbnail',
        'submitted_at', 'reviewed_at', 'rejection_note'
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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
