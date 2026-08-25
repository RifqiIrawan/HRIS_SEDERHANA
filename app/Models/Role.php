<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'ADMIN';

    public const HR = 'HR';

    public const EMPLOYEE = 'EMPLOYEE';

    public const CODES = [self::ADMIN, self::HR, self::EMPLOYEE];

    public const ACTIVE = 'ACTIVE';

    public const INACTIVE = 'INACTIVE';

    protected $fillable = [
        'role_code',
        'role_name',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The menus this role may reach; absence of a row is denial.
     *
     * The pivot carries which verbs the grant covers, so the relation has to
     * bring `actions` back with it — a menu row without it reads as unlimited.
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class)
            ->using(MenuRole::class)
            ->withPivot('actions');
    }
}
