<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $ticket_code
 * @property string $student_name
 * @property string $student_email
 * @property string $subject
 * @property string|null $category
 * @property string|null $priority
 * @property string|null $assigned_to
 * @property string|null $sla_time_left
 * @property bool $is_overdue
 * @property string|null $status
 * @property string|null $message
 * @property string|null $evidence_url
 * @property array|null $timeline
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'student_name',
        'student_email',
        'subject',
        'category',
        'priority',
        'assigned_to',
        'sla_time_left',
        'is_overdue',
        'status',
        'message',
        'evidence_url',
        'timeline',
    ];

    protected $casts = [
        'is_overdue' => 'boolean',
        'timeline' => 'array',
    ];
}
