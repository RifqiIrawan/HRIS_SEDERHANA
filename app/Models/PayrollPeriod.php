<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory;

    public const OPEN = 'OPEN';

    public const PROCESSED = 'PROCESSED';

    public const CLOSED = 'CLOSED';

    protected $fillable = [
        'period_code',
        'period_name',
        'start_date',
        'end_date',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'period_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::OPEN, self::PROCESSED]);
    }

    public function isClosed(): bool
    {
        return $this->status === self::CLOSED;
    }

    /** Spec §44 / PAY-013 — a closed period is frozen until an ADMIN reopens it. */
    public function isEditable(): bool
    {
        return ! $this->isClosed();
    }
}
