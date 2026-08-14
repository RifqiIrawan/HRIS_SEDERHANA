<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    public const DRAFT = 'DRAFT';

    public const FINAL = 'FINAL';

    protected $fillable = [
        'period_id',
        'employee_id',
        'present_days',
        'late_days',
        'working_days',
        'daily_rate',
        'gross_salary',
        'total_deduction',
        'net_salary',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'present_days' => 'integer',
            'late_days' => 'integer',
            'working_days' => 'integer',
            'daily_rate' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function deductions(): HasMany
    {
        return $this->details()->where('detail_type', PayrollDetail::DEDUCTION);
    }
}
