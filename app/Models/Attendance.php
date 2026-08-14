<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    public const PRESENT = 'PRESENT';

    public const LATE = 'LATE';

    public const ABSENT = 'ABSENT';

    public const INCOMPLETE = 'INCOMPLETE';

    public const STATUSES = [self::PRESENT, self::LATE, self::ABSENT, self::INCOMPLETE];

    /** Spec §39 — only these count as a paid working day. */
    public const PAYABLE_STATUSES = [self::PRESENT, self::LATE];

    protected $fillable = [
        'employee_id',
        'roster_id',
        'location_id',
        'attendance_date',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_distance',
        'check_in_photo',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_distance',
        'check_out_photo',
        'late_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy' => 'float',
            'check_in_distance' => 'float',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy' => 'float',
            'check_out_distance' => 'float',
            'late_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function roster(): BelongsTo
    {
        return $this->belongsTo(ShiftRoster::class, 'roster_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AttendancePhoto::class);
    }

    public function scopePayable(Builder $query): Builder
    {
        return $query->whereIn('status', self::PAYABLE_STATUSES);
    }

    public function scopeBetweenDates(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('attendance_date', [$start, $end]);
    }

    public function isCheckedIn(): bool
    {
        return $this->check_in_at !== null;
    }

    public function isCheckedOut(): bool
    {
        return $this->check_out_at !== null;
    }

    public function photoFor(string $type): ?AttendancePhoto
    {
        return $this->photos->firstWhere('photo_type', $type);
    }
}
