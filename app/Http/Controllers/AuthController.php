<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Spec §47 (Authentication) and §56 — session auth with login rate limiting.
 */
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'Email',
            'password' => 'Password',
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => sprintf(
                    'Terlalu banyak percobaan login. Coba lagi dalam %d detik.',
                    RateLimiter::availableIn($throttleKey),
                ),
            ])->status(429);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            // Deliberately vague: revealing which half was wrong would let an
            // attacker enumerate valid accounts.
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->status !== User::ACTIVE) {
            Auth::logout();
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => Carbon::now()])->save();

        AuditLog::record('auth.login', $user, sprintf('%s login', $user->email));

        return $this->ok(
            ['redirect' => route('dashboard')],
            'Login berhasil. Mengalihkan…',
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = $request->user()) {
            AuditLog::record('auth.logout', $user, sprintf('%s logout', $user->email));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function profile(Request $request): View
    {
        $user = $request->user()->load(['role', 'employee']);

        return view('profile.index', compact('user'));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password']])->save();

        AuditLog::record('auth.password_changed', $user, 'Password diubah sendiri oleh pengguna');

        return $this->ok(message: 'Password berhasil diubah.');
    }
}
