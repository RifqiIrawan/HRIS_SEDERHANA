<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    public const ACTIVE = 'ACTIVE';

    public const INACTIVE = 'INACTIVE';

    protected $fillable = [
        'location_code',
        'location_name',
        'address',
        'latitude',
        'longitude',
        'radius_meter',
        'gps_accuracy_limit',
        'status',
    ];

    protected function casts(): array
    {
        return [
            // Cast to float rather than decimal:7 — GeofenceService does
            // trigonometry on these and needs numbers, not numeric strings.
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meter' => 'integer',
            'gps_accuracy_limit' => 'integer',
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function effectiveRadiusMeter(): float
    {
        return (float) ($this->radius_meter ?: config('hris.default_radius_meter'));
    }

    public function effectiveAccuracyLimit(): float
    {
        return (float) ($this->gps_accuracy_limit ?: config('hris.default_gps_accuracy_limit'));
    }
}
