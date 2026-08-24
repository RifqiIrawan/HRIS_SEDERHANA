<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deactivating a user must take effect on their next request, not whenever
 * their session happens to expire.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->status === User::ACTIVE) {
            return $next($request);
        }

        $this->revokeCredentials($request, $user);

        abort(403, 'Akun Anda dinonaktifkan. Hubungi administrator.');
    }

    /**
     * Clears whichever credential this request actually carries.
     *
     * A browser carries a session and no token; a mobile client carries a token
     * and no session. The distinction is not cosmetic: the API stack never runs
     * StartSession, so reaching for $request->session() there throws "Session
     * store not set on request" and the 403 below would never be sent.
     */
    private function revokeCredentials(Request $request, User $user): void
    {
        if ($user->currentAccessToken() instanceof PersonalAccessToken) {
            // Every token, not just this one — a deactivated account must not
            // stay signed in on a second device.
            $user->tokens()->delete();
        }

        if ($request->hasSession()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
