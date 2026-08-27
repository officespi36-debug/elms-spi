<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $academic_year_id
 * @property string|null $code
 * @property string $name
 * @property string|null $parent_year
 * @property int|null $semester_num
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string|null $enrollment_open
 * @property string|null $enrollment_close
 * @property string|null $midterm_exam
 * @property string|null $final_exam
 * @property string|null $payment_due
 * @property float|null $late_fee
 * @property string|null $status
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read AcademicYear|null $academicYear
 */
class Semester extends Model
{
    protected $fillable = [
        'academic_year_id',
        'code',
        'name',
        'parent_year',
        'semester_num',
        'start_date',
        'end_date',
        'enrollment_open',
        'enrollment_close',
        'midterm_exam',
        'final_exam',
        'payment_due',
        'late_fee',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
