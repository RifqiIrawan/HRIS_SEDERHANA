<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token authentication for the Flutter client (HRIS-PARKIR-MOBILE).
 *
 * The web AuthController is left alone: it opens a session and answers with a
 * redirect, neither of which a mobile client can use. What is restated here
 * rather than shared is the *refusal* logic — same throttle, same deliberately
 * vague message, same inactive-account check — because those are rules about
 * signing in, not about the transport carrying it.
 */
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
            // Names the row in personal_access_tokens, so one employee's
            // devices can be told apart when a single handset must be cut off.
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [], [
            'email' => 'Email',
            'password' => 'Password',
        ]);

        $device = trim((string) ($credentials['device_name'] ?? '')) ?: 'mobile';

        // Keyed apart from the web login: exhausting the app's five attempts
        // must not also lock the employee out of the browser, and vice versa.
        $throttleKey = 'api|'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => sprintf(
                    'Terlalu banyak percobaan login. Coba lagi dalam %d detik.',
                    RateLimiter::availableIn($throttleKey),
                ),
            ])->status(429);
        }

        // The provider, not Auth::attempt(): attempting would drive the session
        // guard, and the API stack has no session to drive. Going through the
        // provider still applies the exact hashing configuration the browser
        // login uses, so the two can never disagree about a password.
        $provider = Auth::createUserProvider('users');

        /** @var ?User $user */
        $user = $provider->retrieveByCredentials(['email' => $credentials['email']]);

        if (! $user || ! $provider->validateCredentials($user, ['password' => $credentials['password']])) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            // Deliberately vague: revealing which half was wrong would let an
            // attacker enumerate valid accounts.
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if ($user->status !== User::ACTIVE) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Akun Anda dinonaktifkan. Hubungi administrator.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Reinstalling the app, or signing in again after a crash, must not
        // leave the previous token alive: one row per device, replaced on login.
        $user->tokens()->where('name', $device)->delete();

        // The expiry is passed explicitly rather than left to config alone.
        // Sanctum's guard enforces `sanctum.expiration` against created_at but
        // leaves expires_at null, so the column — and anything reading it, the
        // app included — would claim the token never expires while the guard
        // quietly rejected it after thirty days.
        $expirationMinutes = (int) config('sanctum.expiration');

        $token = $user->createToken(
            $device,
            ['*'],
            $expirationMinutes > 0 ? Carbon::now()->addMinutes($expirationMinutes) : null,
        );

        $user->forceFill(['last_login_at' => Carbon::now()])->save();

        // The actor is passed explicitly rather than seated on the guard.
        // Calling Auth::guard('sanctum')->setUser() here would attribute the row,
        // but it would also leave that user cached on the guard for the rest of
        // the container's life — and wherever the container outlives one request
        // (Octane, the test client), the *next* request would then authenticate
        // as this user without its own token ever being checked.
        AuditLog::record(
            'auth.login_mobile',
            $user,
            sprintf('%s login via %s', $user->email, $device),
            actorId: $user->id,
        );

        return $this->ok([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->format('Y-m-d H:i:s'),
            'user' => $this->profile($user),
        ], 'Login berhasil.');
    }

    /** Signs out this handset only; the employee's other devices stay valid. */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        AuditLog::record('auth.logout_mobile', $user, sprintf('%s logout via mobile', $user->email));

        $user->currentAccessToken()->delete();

        return $this->ok(message: 'Anda telah keluar.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok($this->profile($request->user()));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            // The rule defaults to the session guard, which is absent here, so
            // it is pointed at the guard that actually authenticated this call.
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password']])->save();

        // Changing a password is also how an employee reacts to a lost phone, so
        // every other token dies with it. This one survives, to avoid bouncing
        // the person who just changed it.
        $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();

        AuditLog::record('auth.password_changed', $user, sprintf('%s mengubah password via mobile', $user->email));

        return $this->ok(message: 'Password berhasil diubah.');
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $user): array
    {
        $user->loadMissing(['role', 'employee']);
        $employee = $user->employee;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_code' => $user->role?->role_code,
            'role_name' => $user->role?->role_name,
            // Null for an account with no personnel record — an administrator,
            // typically. The app reads this to explain why the attendance tab is
            // unavailable, instead of letting every screen fail one by one.
            'employee' => $employee ? [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'phone' => $employee->phone,
                'employment_status' => $employee->employment_status,
                'employment_type' => $employee->employment_type,
                'join_date' => $employee->join_date?->toDateString(),
                'status' => $employee->status,
            ] : null,
        ];
    }
}
