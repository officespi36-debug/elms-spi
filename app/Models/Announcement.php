<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title_kh
 * @property string $title_en
 * @property string $body_kh
 * @property string $body_en
 * @property string|null $audience_type
 * @property array|null $audience_filters
 * @property string|null $priority
 * @property array|null $delivery_channels
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property bool $is_pinned
 * @property int|null $pin_days
 * @property bool $require_ack
 * @property bool $allow_comments
 * @property string|null $status
 * @property int $sent_count
 * @property float $read_rate
 * @property array|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_kh',
        'title_en',
        'body_kh',
        'body_en',
        'audience_type',
        'audience_filters',
        'priority',
        'delivery_channels',
        'scheduled_at',
        'is_pinned',
        'pin_days',
        'require_ack',
        'allow_comments',
        'status',
        'sent_count',
        'read_rate',
        'attachments',
    ];

    protected $casts = [
        'audience_filters' => 'array',
        'delivery_channels' => 'array',
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'require_ack' => 'boolean',
        'allow_comments' => 'boolean',
        'scheduled_at' => 'datetime',
    ];
}
