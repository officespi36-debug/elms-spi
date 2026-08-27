<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $email
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device
 * @property string|null $browser
 * @property string|null $status
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 */
class AuthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'status',
        'location',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
