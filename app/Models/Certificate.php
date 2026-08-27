<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $student_id
 * @property int|null $course_id
 * @property int|null $major_id
 * @property int|null $template_id
 * @property string $certificate_number
 * @property string $verification_code
 * @property string|null $grade
 * @property float|null $score
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property string|null $revocation_reason
 * @property string|null $revocation_evidence
 * @property string|null $revocation_note
 * @property bool $show_reason_publicly
 * @property int $downloads_count
 * @property int $verifications_count
 * @property \Illuminate\Support\Carbon|null $last_verified_at
 * @property string|null $file_path
 * @property array|null $audit_trail
 * @property \Illuminate\Support\Carbon|null $issued_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $student
 * @property-read Course|null $course
 */
class Certificate extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'major_id',
        'template_id',
        'certificate_number',
        'verification_code',
        'grade',
        'score',
        'status',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'revocation_evidence',
        'revocation_note',
        'show_reason_publicly',
        'downloads_count',
        'verifications_count',
        'last_verified_at',
        'file_path',
        'audit_trail',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'show_reason_publicly' => 'boolean',
        'audit_trail' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function revokedByAdmin()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }
}
