<?php

namespace App\Http\Middleware;

use App\Services\MenuAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the role → menu mapping at the route, so hiding a sidebar entry
 * actually denies the URL behind it rather than only tidying the navigation.
 *
 * Routes no menu claims pass straight through; see MenuAccessService.
 */
class MenuAccessMiddleware
{
    public function __construct(private readonly MenuAccessService $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        if ($this->access->allowsRoute($user, $request->route()?->getName())) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke menu ini.');
    }
}
