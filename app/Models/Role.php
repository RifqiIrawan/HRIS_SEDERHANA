<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'ADMIN';

    public const HR = 'HR';

    public const EMPLOYEE = 'EMPLOYEE';

    public const CODES = [self::ADMIN, self::HR, self::EMPLOYEE];

    protected $fillable = [
        'role_code',
        'role_name',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
