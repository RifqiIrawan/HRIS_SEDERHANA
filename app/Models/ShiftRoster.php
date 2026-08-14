<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShiftRoster extends Model
{
    use HasFactory;

    public const SCHEDULED = 'SCHEDULED';

    public const OFF = 'OFF';

    public const CANCELLED = 'CANCELLED';

    protected $fillable = [
        'employee_id',
        'location_id',
        'shift_id',
        'roster_date',
        'start_datetime',
        'end_datetime',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'roster_date' => 'date',
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'roster_id');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::SCHEDULED)->whereNotNull('shift_id');
    }

    public function isWorkingDay(): bool
    {
        return $this->status === self::SCHEDULED && $this->shift_id !== null;
    }
}
