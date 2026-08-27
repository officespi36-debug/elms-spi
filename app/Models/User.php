<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property string $name
 * @property string|null $name_kh
 * @property string $email
 * @property string $password
 * @property string $role
 * @property int|null $major_id
 * @property string|null $student_code
 * @property string|null $study_type
 * @property string|null $phone
 * @property string|null $telegram_id
 * @property string|null $telegram_chat_id
 * @property string|null $telegram_username
 * @property string|null $telegram_photo_url
 * @property string|null $google_id
 * @property string|null $clerk_id
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property bool $is_active
 * @property string $status
 * @property string|null $qualification
 * @property string|null $expertise
 * @property int $login_attempts
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property string|null $otp_code
 * @property \Illuminate\Support\Carbon|null $otp_expires_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'name_kh',
        'email',
        'password',
        'role',
        'major_id',
        'student_code',
        'study_type',
        'phone',
        'telegram_id',
        'telegram_chat_id',
        'telegram_username',
        'telegram_photo_url',
        'google_id',
        'clerk_id',
        'avatar',
        'email_verified_at',
        'is_active',
        'status',
        'qualification',
        'expertise',
        'login_attempts',
        'locked_until',
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'locked_until' => 'datetime',
            'otp_expires_at' => 'datetime',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function authLogs()
    {
        return $this->hasMany(AuthLog::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'student_id');
    }
}
