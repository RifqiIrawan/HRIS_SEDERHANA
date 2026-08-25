<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A navigable module: what the sidebar shows and which routes it governs.
 */
class Menu extends Model
{
    use HasFactory;

    public const ACTIVE = 'ACTIVE';

    protected $fillable = [
        'menu_code',
        'menu_name',
        'icon',
        'group_name',
        'route_name',
        'route_patterns',
        'requires_employee',
        'is_action',
        'is_locked',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'route_patterns' => 'array',
        'requires_employee' => 'boolean',
        'is_action' => 'boolean',
        'is_locked' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->using(MenuRole::class)
            ->withPivot('actions');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::ACTIVE);
    }

    /**
     * How specifically this menu claims a route name, or null if it does not.
     *
     * The score is what lets "attendance.monitoring" win over "attendance.*"
     * for the monitoring route: an exact hit outranks any wildcard, and among
     * wildcards the longer pattern is the more specific claim.
     */
    public function matchScore(string $routeName): ?int
    {
        $best = null;

        foreach ($this->route_patterns ?? [] as $pattern) {
            if ($pattern === $routeName) {
                return PHP_INT_MAX;
            }

            if (Str::is($pattern, $routeName)) {
                $best = max($best ?? 0, strlen($pattern));
            }
        }

        return $best;
    }
}
