<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec §7 — route-level role gate, used as `role:ADMIN,HR`.
 *
 * ADMIN is granted everything unconditionally, so routes only ever need to
 * name the narrower roles they are actually for.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        if ($user->isAdmin() || $user->hasRole($roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke menu ini.');
    }

    /**
     * Convenience for building `role:` strings in route files.
     */
    public static function hr(): string
    {
        return 'role:'.Role::ADMIN.','.Role::HR;
    }
}
