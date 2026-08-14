<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Shift extends Model
{
    use HasFactory;

    public const ACTIVE = 'ACTIVE';

    public const INACTIVE = 'INACTIVE';

    /** Pattern token that means "no shift that day" (spec §16). */
    public const OFF = 'OFF';

    protected $fillable = [
        'shift_code',
        'shift_name',
        'start_time',
        'end_time',
        'cross_day',
        'late_tolerance_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cross_day' => 'boolean',
            'late_tolerance_minutes' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(ShiftRoster::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    /**
     * Absolute start of this shift on the given roster date.
     */
    public function startDatetimeFor(CarbonInterface $rosterDate): Carbon
    {
        return $this->applyTime($rosterDate, $this->start_time);
    }

    /**
     * Absolute end of this shift. Spec §17: a cross-day shift ends on the
     * following calendar day, so shift 3 on 12 Aug ends 13 Aug 06:00.
     */
    public function endDatetimeFor(CarbonInterface $rosterDate): Carbon
    {
        $end = $this->applyTime($rosterDate, $this->end_time);

        if ($this->crossesMidnight()) {
            $end->addDay();
        }

        return $end;
    }

    /**
     * Trust the explicit cross_day flag, but also catch the case where the
     * times themselves wrap past midnight and the flag was left unticked.
     */
    public function crossesMidnight(): bool
    {
        return $this->cross_day || $this->end_time <= $this->start_time;
    }

    public function durationMinutes(): int
    {
        $base = Carbon::parse('2000-01-01');

        return (int) $this->startDatetimeFor($base)->diffInMinutes($this->endDatetimeFor($base));
    }

    private function applyTime(CarbonInterface $date, string $time): Carbon
    {
        [$hour, $minute, $second] = array_pad(explode(':', $time), 3, '0');

        return Carbon::parse($date)->setTime((int) $hour, (int) $minute, (int) $second);
    }
}
