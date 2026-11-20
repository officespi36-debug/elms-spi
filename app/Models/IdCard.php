<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IdCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_no',
        'token',
        'issued_at',
        'expires_at',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'issued_at'   => 'datetime',
        'expires_at'  => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::lower(bin2hex(random_bytes(20))); // 40 hex characters
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public static function generateCardNo(User $user): string
    {
        do {
            $year = date('Y');
            $random = strtoupper(Str::random(6));
            $cardNo = "SPI-{$year}-{$random}";
        } while (static::where('card_no', $cardNo)->exists());

        return $cardNo;
    }
}
