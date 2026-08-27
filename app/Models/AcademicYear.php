<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int|null $semesters_count
 * @property string|null $status
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AcademicYear extends Model
{
    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'semesters_count',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }
}
