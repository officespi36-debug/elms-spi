<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $student_id
 * @property int|null $course_id
 * @property int|null $teacher_id
 * @property float $amount
 * @property string $currency
 * @property string|null $aba_transaction_id
 * @property string|null $payment_slip
 * @property string $status
 * @property int|null $verified_by
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $student
 * @property-read Course|null $course
 * @property-read User|null $teacher
 * @property-read User|null $verifier
 */
class Payment extends Model
{
    protected $fillable = [
        'student_id', 'course_id', 'teacher_id', 'amount', 'currency',
        'aba_transaction_id', 'payment_slip', 'status', 'verified_by', 'verified_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
