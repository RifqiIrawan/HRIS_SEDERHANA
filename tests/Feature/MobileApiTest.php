<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The token API the Flutter client (HRIS-PARKIR-MOBILE) talks to.
 *
 * The cases here are the ones the web suite cannot cover, because they are about
 * the differences between the two stacks: there is no session to fall back on, no
 * menu mapping to authorise against, and a token that has to die the moment the
 * account behind it does.
 */
class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(array $userAttributes = []): User
    {
        $employee = Employee::factory()->create(['status' => Employee::ACTIVE]);

        // array_merge, not `+`: with `+` the left operand wins on a duplicate
        // key, so an explicit INACTIVE would be silently overridden by the
        // default and the test would assert nothing.
        return User::factory()
            ->forEmployee($employee)
            ->create(array_merge(['status' => User::ACTIVE], $userAttributes));
    }

    /**
     * Issues the next request as this token, with the guard cache cleared first.
     *
     * The test client keeps one container across every call in a method, and
     * RequestGuard caches the user it resolved the first time — it never reads
     * the Authorization header again. Without forgetting the guards, a token
     * revoked mid-test would appear to keep working, and every assertion below
     * about revocation would pass vacuously. A deployment builds a fresh
     * container per request and never sees the effect: this is scaffolding for
     * the test client, not a workaround for the application.
     */
    private function withFreshToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    private function tokenFor(User $user, string $device = 'test-device'): string
    {
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => $device,
        ]);

        return $response->json('data.token');
    }

    #[Test]
    public function login_issues_a_token_and_the_employee_profile(): void
    {
        $user = $this->employeeUser();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'pixel-7',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_at', 'user' => ['employee']],
            ])
            ->assertJsonPath('data.user.employee.id', $user->employee_id);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'pixel-7',
        ]);
    }

    #[Test]
    public function the_issued_token_carries_the_configured_expiry(): void
    {
        // Sanctum enforces sanctum.expiration against created_at but leaves the
        // column null unless the expiry is passed in. A null here would tell the
        // app the token lasts forever while the guard rejected it after 30 days.
        config(['sanctum.expiration' => 60]);

        $user = $this->employeeUser();

        $expiresAt = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('data.expires_at');

        $this->assertNotNull($expiresAt);
    }

    #[Test]
    public function a_wrong_password_is_refused_without_saying_which_half_was_wrong(): void
    {
        $user = $this->employeeUser();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'salah-sekali',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email atau password salah.');

        // The same message for an address that does not exist at all.
        $this->postJson('/api/login', [
            'email' => 'tidak-ada@hris.test',
            'password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email atau password salah.');
    }

    #[Test]
    public function an_inactive_account_cannot_obtain_a_token(): void
    {
        $user = $this->employeeUser(['status' => User::INACTIVE]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function logging_in_again_on_the_same_device_replaces_that_devices_token(): void
    {
        $user = $this->employeeUser();

        $first = $this->tokenFor($user, 'pixel-7');
        $second = $this->tokenFor($user, 'pixel-7');

        $this->assertNotSame($first, $second);

        // One row for that handset, not a growing pile.
        $this->assertSame(1, $user->tokens()->where('name', 'pixel-7')->count());

        $this->withFreshToken($first)->getJson('/api/me')->assertStatus(401);
        $this->withFreshToken($second)->getJson('/api/me')->assertOk();
    }

    #[Test]
    public function a_protected_endpoint_refuses_an_anonymous_caller_with_json(): void
    {
        $this->getJson('/api/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function a_request_without_an_accept_header_still_gets_json(): void
    {
        // The controllers are shared with the browser and pick their format from
        // Accept; without ForceJsonResponse this would render a Blade page.
        $response = $this->get('/api/me');

        $response->assertStatus(401);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function deactivating_an_account_is_a_403_on_the_api_stack_and_revokes_its_tokens(): void
    {
        // The regression this guards: EnsureUserIsActive used to reach for
        // $request->session(), which does not exist on the API stack and would
        // raise "Session store not set on request" instead of this 403.
        $user = $this->employeeUser();
        $token = $this->tokenFor($user);

        $user->forceFill(['status' => User::INACTIVE])->save();

        $this->withFreshToken($token)->getJson('/api/attendance')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Akun Anda dinonaktifkan. Hubungi administrator.');

        $this->assertSame(0, $user->tokens()->count());

        // And the handset is bounced to the login screen on its next call.
        $this->withFreshToken($token)->getJson('/api/me')->assertStatus(401);
    }

    #[Test]
    public function attendance_history_stays_self_scoped_even_for_an_hr_user(): void
    {
        // The web listing widens for HR, which is right for the monitoring screen
        // and wrong for a personal app.
        $hrEmployee = Employee::factory()->create(['status' => Employee::ACTIVE]);
        $hr = User::factory()->hr()->create([
            'employee_id' => $hrEmployee->id,
            'status' => User::ACTIVE,
        ]);

        $other = $this->employeeUser();

        $token = $this->tokenFor($hr);

        $this->withFreshToken($token)
            ->getJson('/api/attendance/history?employee_id='.$other->employee_id)
            ->assertOk()
            // A crafted employee_id must not widen the result to someone else.
            ->assertJsonPath('data.items', []);
    }

    #[Test]
    public function an_account_with_no_employee_record_is_told_so_rather_than_erroring(): void
    {
        $user = User::factory()->hr()->create([
            'employee_id' => null,
            'status' => User::ACTIVE,
        ]);

        $this->withFreshToken($this->tokenFor($user))
            ->getJson('/api/attendance')
            ->assertStatus(403)
            ->assertJsonPath('code', 'NOT_LINKED_TO_EMPLOYEE');
    }

    #[Test]
    public function only_final_payslips_are_published(): void
    {
        $user = $this->employeeUser();

        $period = PayrollPeriod::create([
            'period_code' => 'P-2026-08',
            'period_name' => 'Agustus 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => PayrollPeriod::PROCESSED,
        ]);

        $draft = Payroll::create([
            'period_id' => $period->id,
            'employee_id' => $user->employee_id,
            'present_days' => 20,
            'late_days' => 1,
            'working_days' => 22,
            'daily_rate' => 150000,
            'gross_salary' => 3000000,
            'total_deduction' => 0,
            'net_salary' => 3000000,
            'status' => Payroll::DRAFT,
        ]);

        $token = $this->tokenFor($user);

        // A draft figure still moves, so it is neither listed nor readable.
        $this->withFreshToken($token)->getJson('/api/payroll/slips')
            ->assertOk()
            ->assertJsonPath('data.items', []);

        $this->withFreshToken($token)->getJson('/api/payroll/slips/'.$draft->id)
            ->assertStatus(422)
            ->assertJsonPath('code', 'PAYROLL_NOT_FINAL');

        $draft->forceFill(['status' => Payroll::FINAL])->save();

        $this->withFreshToken($token)->getJson('/api/payroll/slips')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.net_salary', 3000000);
    }

    #[Test]
    public function a_payslip_belonging_to_someone_else_is_refused(): void
    {
        $mine = $this->employeeUser();
        $theirs = $this->employeeUser();

        $period = PayrollPeriod::create([
            'period_code' => 'P-2026-09',
            'period_name' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => PayrollPeriod::CLOSED,
        ]);

        $foreign = Payroll::create([
            'period_id' => $period->id,
            'employee_id' => $theirs->employee_id,
            'present_days' => 20,
            'late_days' => 0,
            'working_days' => 22,
            'daily_rate' => 150000,
            'gross_salary' => 3000000,
            'total_deduction' => 0,
            'net_salary' => 3000000,
            'status' => Payroll::FINAL,
        ]);

        // Route-model binding resolves it happily; the ownership check is the
        // authorisation, not the route.
        $this->withFreshToken($this->tokenFor($mine))
            ->getJson('/api/payroll/slips/'.$foreign->id)
            ->assertStatus(403);
    }

    #[Test]
    public function logout_revokes_only_the_calling_device(): void
    {
        $user = $this->employeeUser();

        $phone = $this->tokenFor($user, 'pixel-7');
        $tablet = $this->tokenFor($user, 'tab-a8');

        $this->withFreshToken($phone)->postJson('/api/logout')->assertOk();

        $this->withFreshToken($phone)->getJson('/api/me')->assertStatus(401);
        $this->withFreshToken($tablet)->getJson('/api/me')->assertOk();
    }

    #[Test]
    public function changing_the_password_keeps_this_device_and_drops_the_others(): void
    {
        $user = $this->employeeUser();

        $phone = $this->tokenFor($user, 'pixel-7');
        $lost = $this->tokenFor($user, 'hp-hilang');

        $this->withFreshToken($phone)->putJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'rahasia-baru-123',
            'password_confirmation' => 'rahasia-baru-123',
        ])->assertOk();

        $this->withFreshToken($phone)->getJson('/api/me')->assertOk();
        $this->withFreshToken($lost)->getJson('/api/me')->assertStatus(401);
    }

    #[Test]
    public function the_password_rule_reads_the_token_guard_not_the_session(): void
    {
        // 'current_password' defaults to the session guard, which is absent here;
        // pointed at the wrong guard the rule rejects even a correct password.
        $user = $this->employeeUser();

        $this->withFreshToken($this->tokenFor($user))->putJson('/api/me/password', [
            'current_password' => 'password-yang-salah',
            'password' => 'rahasia-baru-123',
            'password_confirmation' => 'rahasia-baru-123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Password saat ini salah.');
    }

    #[Test]
    public function the_administrative_web_routes_are_not_reachable_from_the_api(): void
    {
        $user = $this->employeeUser();
        $token = $this->tokenFor($user);

        // Master data, payroll processing and HR monitoring stay on the web
        // stack; a token must not open any of them.
        foreach (['/api/users', '/api/employees', '/api/payroll/periods', '/api/attendance/monitoring'] as $path) {
            $this->withFreshToken($token)->getJson($path)->assertNotFound();
        }
    }
}
