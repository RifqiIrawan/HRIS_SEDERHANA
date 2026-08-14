<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    public const ACTIVE = 'ACTIVE';

    public const INACTIVE = 'INACTIVE';

    protected $fillable = [
        'employee_id',
        'location_id',
        'shift_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    /**
     * Assignments that cover the given date. An assignment with no end_date is
     * open-ended and covers every date from start_date onwards.
     */
    public function scopeCovering(Builder $query, string $date): Builder
    {
        return $query->whereDate('start_date', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date);
            });
    }

    /**
     * Two assignments clash when they are for the same employee and their date
     * ranges overlap — regardless of shift, since one person cannot be rostered
     * to two posts at once (spec §18).
     */
    public function scopeOverlapping(Builder $query, string $startDate, ?string $endDate): Builder
    {
        return $query->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
            ->where(function (Builder $q) use ($startDate) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate);
            });
    }
}
