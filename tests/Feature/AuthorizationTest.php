<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Spec §7 (role matrix) and §56 (security minimum) — login, session and
 * route-level authorisation.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('user@hris.test|127.0.0.1');
    }

    /* ── Login ───────────────────────────────────────────────────────── */

    #[Test]
    public function a_valid_login_starts_a_session_and_stamps_the_last_login(): void
    {
        $user = User::factory()->admin()->create(['email' => 'admin@hris.test']);

        $this->postJson(route('login.attempt'), [
            'email' => 'admin@hris.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect', route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
    }

    #[Test]
    public function a_wrong_password_is_refused_without_revealing_which_half_was_wrong(): void
    {
        User::factory()->admin()->create(['email' => 'admin@hris.test']);

        $response = $this->postJson(route('login.attempt'), [
            'email' => 'admin@hris.test',
            'password' => 'salah-sekali',
        ])->assertStatus(422);

        $this->assertSame('Email atau password salah.', $response->json('message'));
        $this->assertGuest();
    }

    #[Test]
    public function an_unknown_email_gets_the_same_message_as_a_wrong_password(): void
    {
        $response = $this->postJson(route('login.attempt'), [
            'email' => 'tidak-ada@hris.test',
            'password' => 'password',
        ])->assertStatus(422);

        $this->assertSame('Email atau password salah.', $response->json('message'));
    }

    #[Test]
    public function an_inactive_account_cannot_log_in(): void
    {
        User::factory()->admin()->inactive()->create(['email' => 'nonaktif@hris.test']);

        $this->postJson(route('login.attempt'), [
            'email' => 'nonaktif@hris.test',
            'password' => 'password',
        ])->assertStatus(422);

        $this->assertGuest();
    }

    /** Spec §56 — login rate limiting. */
    #[Test]
    public function repeated_failures_are_throttled(): void
    {
        User::factory()->admin()->create(['email' => 'admin@hris.test']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('login.attempt'), [
                'email' => 'admin@hris.test',
                'password' => 'salah',
            ])->assertStatus(422);
        }

        $this->postJson(route('login.attempt'), [
            'email' => 'admin@hris.test',
            'password' => 'password',
        ])->assertStatus(429);
    }

    #[Test]
    public function a_deactivated_user_is_logged_out_on_their_next_request(): void
    {
        $user = User::factory()->hr()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['status' => User::INACTIVE]);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    #[Test]
    public function guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function logout_clears_the_session(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /* ── Role matrix (spec §7) ───────────────────────────────────────── */

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function roleMatrix(): array
    {
        return [
            // ADMIN — full access.
            'ADMIN → users' => [Role::ADMIN, 'users.index', 200],
            'ADMIN → roles' => [Role::ADMIN, 'roles.index', 200],
            'ADMIN → employees' => [Role::ADMIN, 'employees.index', 200],
            'ADMIN → payroll' => [Role::ADMIN, 'payroll.index', 200],

            // HR — operational modules, but not user/role administration.
            'HR → users' => [Role::HR, 'users.index', 403],
            'HR → roles' => [Role::HR, 'roles.index', 403],
            'HR → employees' => [Role::HR, 'employees.index', 200],
            'HR → locations' => [Role::HR, 'locations.index', 200],
            'HR → shifts' => [Role::HR, 'shifts.index', 200],
            'HR → assignments' => [Role::HR, 'assignments.index', 200],
            'HR → rosters' => [Role::HR, 'rosters.index', 200],
            'HR → monitoring' => [Role::HR, 'attendance.monitoring', 200],
            'HR → payroll periods' => [Role::HR, 'payroll.periods', 200],
            'HR → reports' => [Role::HR, 'reports.attendance', 200],

            // EMPLOYEE — dashboard, own attendance, own history, profile only.
            'EMPLOYEE → users' => [Role::EMPLOYEE, 'users.index', 403],
            'EMPLOYEE → employees' => [Role::EMPLOYEE, 'employees.index', 403],
            'EMPLOYEE → locations' => [Role::EMPLOYEE, 'locations.index', 403],
            'EMPLOYEE → shifts' => [Role::EMPLOYEE, 'shifts.index', 403],
            'EMPLOYEE → rosters' => [Role::EMPLOYEE, 'rosters.index', 403],
            'EMPLOYEE → monitoring' => [Role::EMPLOYEE, 'attendance.monitoring', 403],
            'EMPLOYEE → payroll' => [Role::EMPLOYEE, 'payroll.index', 403],
            'EMPLOYEE → payroll periods' => [Role::EMPLOYEE, 'payroll.periods', 403],
            'EMPLOYEE → reports' => [Role::EMPLOYEE, 'reports.payroll', 403],
            'EMPLOYEE → dashboard' => [Role::EMPLOYEE, 'dashboard', 200],
            'EMPLOYEE → own history' => [Role::EMPLOYEE, 'attendance.history', 200],
            'EMPLOYEE → profile' => [Role::EMPLOYEE, 'profile', 200],
        ];
    }

    #[Test]
    #[DataProvider('roleMatrix')]
    public function it_enforces_the_role_matrix(string $roleCode, string $routeName, int $expectedStatus): void
    {
        $user = $roleCode === Role::EMPLOYEE
            ? User::factory()->forEmployee(Employee::factory()->create())->create()
            : User::factory()->role($roleCode)->create();

        $this->actingAs($user)->get(route($routeName))->assertStatus($expectedStatus);
    }

    /* ── Data scoping ────────────────────────────────────────────────── */

    #[Test]
    public function an_employee_only_sees_their_own_attendance_history(): void
    {
        $mine = Employee::factory()->create();
        $theirs = Employee::factory()->create();

        Attendance::factory()->create(['employee_id' => $mine->id]);
        Attendance::factory()->create([
            'employee_id' => $theirs->id,
            'attendance_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs(User::factory()->forEmployee($mine)->create())
            ->getJson(route('attendance.history'))
            ->assertOk();

        $codes = collect($response->json('data.items'))->pluck('employee_code');

        $this->assertCount(1, $codes);
        $this->assertSame($mine->employee_code, $codes->first());
    }

    #[Test]
    public function hr_sees_every_employees_history(): void
    {
        Attendance::factory()->create(['employee_id' => Employee::factory()->create()->id]);
        Attendance::factory()->create([
            'employee_id' => Employee::factory()->create()->id,
            'attendance_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs(User::factory()->hr()->create())
            ->getJson(route('attendance.history'))
            ->assertOk();

        $this->assertCount(2, $response->json('data.items'));
    }

    /* ── CSRF (spec §55) ─────────────────────────────────────────────── */

    /**
     * Laravel short-circuits ValidateCsrfToken while the test suite runs, so a
     * tokenless POST cannot be exercised end-to-end here. What is worth
     * asserting is that the protection is genuinely attached to the
     * state-changing routes rather than assumed.
     */
    #[Test]
    public function csrf_protection_is_attached_to_state_changing_routes(): void
    {
        // The HTTP kernel is what copies the middleware groups onto the router,
        // and it is only built when a request is handled — so resolve it before
        // inspecting the groups.
        app(Kernel::class);

        $router = app(Router::class);

        // The `web` group is where CSRF lives; assert both halves of the chain
        // rather than trusting that "it is in web" by convention.
        $this->assertContains(
            ValidateCsrfToken::class,
            $router->getMiddlewareGroups()['web'],
            'Middleware group "web" tidak memuat proteksi CSRF.',
        );

        foreach (['employees.store', 'attendance.check-in', 'payroll.periods.store'] as $name) {
            $route = $router->getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route {$name} tidak terdaftar.");

            // gatherRouteMiddleware expands the group, so this is the fully
            // resolved chain the request would actually run through.
            $this->assertContains(
                ValidateCsrfToken::class,
                $router->gatherRouteMiddleware($route),
                "Route {$name} tidak dilindungi CSRF.",
            );
        }
    }

    /** Spec §55 — the token the jQuery layer reads must be on the page. */
    #[Test]
    public function the_csrf_token_is_published_for_the_ajax_layer(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="csrf-token"', false);

        $this->actingAs(User::factory()->hr()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('name="csrf-token"', false);
    }
}
