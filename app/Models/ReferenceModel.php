<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared behaviour for the small code/name lists behind the Karyawan form.
 *
 * Each list is its own table (employment_statuses, employment_types,
 * employee_statuses) so a foreign key or an export can name it directly, but
 * the shape is identical, and so is every rule about it — hence one base class
 * rather than three copies.
 *
 * Subclasses declare EMPLOYEE_COLUMN: the employees column that stores this
 * list's code, which is what the delete guard checks before removing a row.
 */
abstract class ReferenceModel extends Model
{
    public const ACTIVE = 'ACTIVE';

    public const INACTIVE = 'INACTIVE';

    /** The employees column holding this list's code. Overridden per subclass. */
    public const EMPLOYEE_COLUMN = '';

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_system' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    /** The order the dropdowns render in: explicit first, then alphabetical. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    /** How many employees currently carry this code. */
    public function usageCount(): int
    {
        return Employee::where(static::EMPLOYEE_COLUMN, $this->code)->count();
    }
}
