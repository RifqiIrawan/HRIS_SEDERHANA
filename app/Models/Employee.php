<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    public const ACTIVE = 'ACTIVE';

    public const INACTIVE = 'INACTIVE';

    public const RESIGNED = 'RESIGNED';

    /*
     * The three constants above are the codes the application itself compares
     * against (scopeActive, the assignment/roster pickers, the attendance
     * guard). The *list* of allowed values is no longer here: employment_status,
     * employment_type and status are validated against the employment_statuses,
     * employment_types and employee_statuses masters, so an administrator can
     * add a value without a deploy. See ReferenceModel.
     */

    protected $fillable = [
        'employee_code',
        'nik',
        'full_name',
        'photo',
        'gender',
        'birth_place',
        'birth_date',
        'phone',
        'address',
        'employment_status',
        'employment_type',
        'join_date',
        'daily_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
            'daily_rate' => 'decimal:2',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(ShiftRoster::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }
}
