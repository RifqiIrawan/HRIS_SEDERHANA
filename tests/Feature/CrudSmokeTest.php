<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\EmploymentStatus;
use App\Models\EmploymentType;
use App\Models\Location;
use App\Models\Menu;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Audit sementara: menelusuri setiap menu master data melalui
 * index → store → show → update → destroy sebagai ADMIN, ditambah satu
 * lintasan baca untuk layar yang bukan CRUD.
 */
class CrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $employee = Employee::factory()->create(['status' => 'ACTIVE']);

        $this->admin = User::factory()->admin()->create([
            'employee_id' => $employee->id,
            'status' => User::ACTIVE,
        ]);

        $this->actingAs($this->admin);
    }

    public static function readOnlyScreens(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'profile' => ['/profile'],
            'attendance' => ['/attendance'],
            'attendance history' => ['/attendance/history'],
            'attendance monitoring' => ['/attendance/monitoring'],
            'users' => ['/users'],
            'roles' => ['/roles'],
            'employees' => ['/employees'],
            'employment statuses' => ['/employment-statuses'],
            'employment types' => ['/employment-types'],
            'employee statuses' => ['/employee-statuses'],
            'locations' => ['/locations'],
            'shifts' => ['/shifts'],
            'assignments' => ['/assignments'],
            'rosters' => ['/rosters'],
            'payroll periods' => ['/payroll/periods'],
            'payroll' => ['/payroll'],
            'report attendance' => ['/reports/attendance'],
            'report payroll' => ['/reports/payroll'],
        ];
    }

    #[Test]
    #[DataProvider('readOnlyScreens')]
    public function test_screen_renders_a_page(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    #[Test]
    #[DataProvider('readOnlyScreens')]
    public function test_screen_returns_json_for_ajax(string $uri): void
    {
        $this->getJson($uri)->assertOk();
    }

    #[Test]
    public function test_lookups_and_menu_access_endpoints(): void
    {
        $this->getJson('/lookups')->assertOk();
        $this->getJson('/roles/menu-access')->assertOk();

        $hr = Role::where('role_code', Role::HR)->firstOrFail();
        $menuIds = Menu::whereIn('menu_code', ['dashboard', 'employees'])->pluck('id')->all();

        $this->putJson('/roles/menu-access', [
            'role_id' => $hr->id,
            'menus' => $menuIds,
        ])->assertOk();

        $this->assertEqualsCanonicalizing($menuIds, $hr->menus()->pluck('menus.id')->all());
    }

    #[Test]
    public function test_menu_access_save_leaves_other_roles_alone(): void
    {
        // The screen edits one role at a time, so a save must not touch a role
        // it was not looking at — the whole reason the matrix was replaced.
        $admin = Role::where('role_code', Role::ADMIN)->firstOrFail();
        $hr = Role::where('role_code', Role::HR)->firstOrFail();

        $before = $admin->menus()->pluck('menus.id')->all();

        $this->putJson('/roles/menu-access', ['role_id' => $hr->id, 'menus' => []])->assertOk();

        $this->assertEqualsCanonicalizing($before, $admin->menus()->pluck('menus.id')->all());
        $this->assertSame([], $hr->menus()->pluck('menus.id')->all());
    }

    #[Test]
    public function test_menu_access_cannot_strip_a_locked_menu_from_admin(): void
    {
        // Role and User are the only screens that can hand access back, so
        // unmapping them from ADMIN would be an unrecoverable lockout.
        $admin = Role::where('role_code', Role::ADMIN)->firstOrFail();
        $locked = Menu::where('is_locked', true)->pluck('id')->all();

        $this->assertNotEmpty($locked, 'the seeded registry should lock at least one menu');

        $this->putJson('/roles/menu-access', ['role_id' => $admin->id, 'menus' => []])->assertOk();

        $this->assertEqualsCanonicalizing($locked, $admin->menus()->pluck('menus.id')->all());
    }

    #[Test]
    public function test_employee_crud(): void
    {
        $create = [
            'employee_code' => 'JP900', 'full_name' => 'Uji Karyawan', 'gender' => 'L',
            'employment_status' => 'PERCOBAAN', 'employment_type' => 'DAILY',
            'join_date' => '2026-01-02', 'daily_rate' => 120000, 'status' => 'ACTIVE',
        ];

        $this->postJson('/employees', $create)->assertStatus(201);
        $id = Employee::where('employee_code', 'JP900')->value('id');

        $this->getJson("/employees/{$id}")->assertOk()->assertJsonFragment(['full_name' => 'Uji Karyawan']);
        $this->putJson("/employees/{$id}", array_merge($create, ['full_name' => 'Uji Karyawan Ubah']))->assertOk();
        $this->assertSame('Uji Karyawan Ubah', Employee::find($id)->full_name);

        $this->deleteJson("/employees/{$id}")->assertOk();
        $this->assertNull(Employee::find($id));
    }

    /**
     * The three Karyawan reference masters share one controller, so one data
     * provider walks all three through the same CRUD lifecycle.
     *
     * @return array<string, array{0: string, 1: class-string}>
     */
    public static function referenceMasters(): array
    {
        return [
            'employment status' => ['/employment-statuses', EmploymentStatus::class],
            'employment type' => ['/employment-types', EmploymentType::class],
            'employee status' => ['/employee-statuses', EmployeeStatus::class],
        ];
    }

    #[Test]
    #[DataProvider('referenceMasters')]
    public function test_reference_master_crud(string $uri, string $model): void
    {
        $create = ['code' => 'UJI_REF', 'name' => 'Uji Referensi', 'sort_order' => 90, 'status' => 'ACTIVE'];

        $this->postJson($uri, $create)->assertStatus(201);
        $id = $model::where('code', 'UJI_REF')->value('id');

        $this->getJson("{$uri}/{$id}")->assertOk()->assertJsonFragment(['name' => 'Uji Referensi']);
        $this->putJson("{$uri}/{$id}", array_merge($create, ['name' => 'Uji Referensi Ubah']))->assertOk();
        $this->assertSame('Uji Referensi Ubah', $model::find($id)->name);

        $this->deleteJson("{$uri}/{$id}")->assertOk();
        $this->assertNull($model::find($id));
    }

    #[Test]
    #[DataProvider('referenceMasters')]
    public function test_reference_master_rejects_a_duplicate_code(string $uri, string $model): void
    {
        $existing = $model::query()->firstOrFail();

        $this->postJson($uri, ['code' => $existing->code, 'name' => 'Duplikat', 'status' => 'ACTIVE'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    #[Test]
    public function test_reference_master_protects_a_system_row(): void
    {
        $active = EmployeeStatus::where('code', 'ACTIVE')->firstOrFail();

        // Renaming or deactivating ACTIVE would break every scopeActive() call.
        $this->putJson("/employee-statuses/{$active->id}", [
            'code' => 'AKTIF', 'name' => 'Aktif', 'status' => 'ACTIVE',
        ])->assertStatus(422);

        $this->putJson("/employee-statuses/{$active->id}", [
            'code' => 'ACTIVE', 'name' => 'Aktif', 'status' => 'INACTIVE',
        ])->assertStatus(422);

        $this->deleteJson("/employee-statuses/{$active->id}")->assertStatus(422);

        $this->assertDatabaseHas('employee_statuses', ['code' => 'ACTIVE', 'status' => 'ACTIVE']);
    }

    #[Test]
    public function test_reference_master_deactivates_a_row_still_in_use(): void
    {
        $type = EmploymentType::create([
            'code' => 'MONTHLY', 'name' => 'Bulanan', 'sort_order' => 20, 'status' => 'ACTIVE',
        ]);

        Employee::factory()->create(['employment_type' => 'MONTHLY']);

        $this->deleteJson("/employment-types/{$type->id}")->assertOk();

        $this->assertDatabaseHas('employment_types', ['code' => 'MONTHLY', 'status' => 'INACTIVE']);
    }

    #[Test]
    public function test_renaming_a_reference_code_carries_its_employees_along(): void
    {
        $status = EmploymentStatus::where('code', 'KONTRAK')->firstOrFail();
        $employee = Employee::factory()->create(['employment_status' => 'KONTRAK']);

        $this->putJson("/employment-statuses/{$status->id}", [
            'code' => 'PKWT', 'name' => 'Perjanjian Kerja Waktu Tertentu', 'status' => 'ACTIVE',
        ])->assertOk();

        $this->assertSame('PKWT', $employee->fresh()->employment_status);
    }

    #[Test]
    public function test_employee_rejects_a_value_absent_from_the_masters(): void
    {
        $this->postJson('/employees', [
            'employee_code' => 'JP901', 'full_name' => 'Uji Karyawan', 'daily_rate' => 100000,
            'employment_status' => 'TIDAK_ADA', 'employment_type' => 'DAILY', 'status' => 'ACTIVE',
        ])->assertStatus(422)->assertJsonValidationErrors('employment_status');
    }

    #[Test]
    public function test_location_crud(): void
    {
        $create = [
            'location_code' => 'LOK900', 'location_name' => 'Uji Lokasi',
            'latitude' => -6.2, 'longitude' => 106.8,
            'radius_meter' => 5, 'gps_accuracy_limit' => 50, 'status' => 'ACTIVE',
        ];

        $this->postJson('/locations', $create)->assertStatus(201);
        $id = Location::where('location_code', 'LOK900')->value('id');

        $this->getJson("/locations/{$id}")->assertOk();
        $this->putJson("/locations/{$id}", array_merge($create, ['location_name' => 'Uji Lokasi Ubah']))->assertOk();
        $this->assertSame('Uji Lokasi Ubah', Location::find($id)->location_name);

        $this->deleteJson("/locations/{$id}")->assertOk();
        $this->assertNull(Location::find($id));
    }

    #[Test]
    public function test_shift_crud(): void
    {
        $create = [
            'shift_code' => 'SH900', 'shift_name' => 'Uji Shift',
            'start_time' => '07:00', 'end_time' => '15:00', 'cross_day' => 0,
            'late_tolerance_minutes' => 15, 'status' => 'ACTIVE',
        ];

        $this->postJson('/shifts', $create)->assertStatus(201);
        $id = Shift::where('shift_code', 'SH900')->value('id');

        $this->getJson("/shifts/{$id}")->assertOk();
        $this->putJson("/shifts/{$id}", array_merge($create, ['shift_name' => 'Uji Shift Ubah']))->assertOk();
        $this->assertSame('Uji Shift Ubah', Shift::find($id)->shift_name);

        $this->deleteJson("/shifts/{$id}")->assertOk();
        $this->assertNull(Shift::find($id));
    }

    #[Test]
    public function test_role_crud(): void
    {
        $create = ['role_code' => 'SUPERVISOR', 'role_name' => 'Supervisor', 'status' => 'ACTIVE'];

        $this->postJson('/roles', $create)->assertStatus(201);
        $id = Role::where('role_code', 'SUPERVISOR')->value('id');

        $this->getJson("/roles/{$id}")->assertOk();
        $this->putJson("/roles/{$id}", array_merge($create, ['role_name' => 'Supervisor Ubah']))->assertOk();
        $this->assertSame('Supervisor Ubah', Role::find($id)->role_name);

        $this->deleteJson("/roles/{$id}")->assertOk();
        $this->assertNull(Role::find($id));
    }

    #[Test]
    public function test_user_crud(): void
    {
        $employee = Employee::factory()->create();
        $roleId = Role::where('role_code', Role::HR)->value('id');

        $create = [
            'name' => 'Uji User', 'email' => 'uji.user@example.test',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'role_id' => $roleId, 'employee_id' => $employee->id, 'status' => 'ACTIVE',
        ];

        $this->postJson('/users', $create)->assertStatus(201);
        $id = User::where('email', 'uji.user@example.test')->value('id');

        $this->getJson("/users/{$id}")->assertOk();
        $this->putJson("/users/{$id}", array_merge($create, [
            'name' => 'Uji User Ubah', 'password' => null, 'password_confirmation' => null,
        ]))->assertOk();
        $this->assertSame('Uji User Ubah', User::find($id)->name);

        $this->deleteJson("/users/{$id}")->assertOk();
        $this->assertNull(User::find($id));
    }

    #[Test]
    public function test_assignment_crud(): void
    {
        $employee = Employee::factory()->create(['status' => 'ACTIVE']);
        $location = Location::factory()->create(['status' => 'ACTIVE']);
        $shift = Shift::factory()->create(['status' => 'ACTIVE']);

        $create = [
            'employee_id' => $employee->id, 'location_id' => $location->id, 'shift_id' => $shift->id,
            'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'status' => 'ACTIVE',
        ];

        $this->postJson('/assignments', $create)->assertStatus(201);
        $id = Assignment::where('employee_id', $employee->id)->value('id');

        $this->getJson("/assignments/{$id}")->assertOk();
        $this->putJson("/assignments/{$id}", array_merge($create, ['end_date' => '2026-10-31']))->assertOk();
        $this->getJson("/assignments/employee/{$employee->id}/shifts")->assertOk();

        $this->deleteJson("/assignments/{$id}")->assertOk();
        $this->assertNull(Assignment::find($id));
    }

    #[Test]
    public function test_payroll_period_crud(): void
    {
        $create = [
            'period_code' => '2026-09', 'period_name' => 'September 2026',
            'start_date' => '2026-09-01', 'end_date' => '2026-09-30',
        ];

        $this->postJson('/payroll/periods', $create)->assertStatus(201);
        $id = PayrollPeriod::where('period_code', '2026-09')->value('id');

        $this->getJson("/payroll/periods/{$id}")->assertOk();
        $this->putJson("/payroll/periods/{$id}", array_merge($create, ['period_name' => 'Sept 2026']))->assertOk();
        $this->assertSame('Sept 2026', PayrollPeriod::find($id)->period_name);

        $this->deleteJson("/payroll/periods/{$id}")->assertOk();
        $this->assertNull(PayrollPeriod::find($id));
    }

    #[Test]
    public function test_roster_generate_and_edit(): void
    {
        $employee = Employee::factory()->create(['status' => 'ACTIVE']);
        $location = Location::factory()->create(['status' => 'ACTIVE']);
        $shift = Shift::factory()->create(['status' => 'ACTIVE']);

        // Preview takes the pattern as a comma-separated string, generate as an
        // array — the two endpoints do not share a form request.
        $this->postJson('/rosters/preview', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-07',
            'pattern' => $shift->shift_code.',OFF',
        ])->assertOk();

        $this->postJson('/rosters/generate', [
            'employee_ids' => [$employee->id],
            'location_id' => $location->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-07',
            'pattern' => [$shift->shift_code, 'OFF'],
        ])->assertOk();

        $roster = ShiftRoster::where('employee_id', $employee->id)->firstOrFail();

        $this->getJson("/rosters/{$roster->id}")->assertOk();
        $this->putJson("/rosters/{$roster->id}", [
            'shift_id' => $shift->id,
            'location_id' => $location->id,
            'status' => ShiftRoster::SCHEDULED,
        ])->assertOk();

        $this->deleteJson("/rosters/{$roster->id}")->assertOk();
        $this->assertNull(ShiftRoster::find($roster->id));
    }

    #[Test]
    public function test_payroll_process_actions(): void
    {
        $employee = Employee::factory()->create(['status' => 'ACTIVE', 'daily_rate' => 100000]);
        $period = PayrollPeriod::factory()->create();

        $this->postJson("/payroll/{$period->id}/generate")->assertOk();
        $this->getJson("/payroll/{$period->id}")->assertOk();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->postJson("/payroll/{$payroll->id}/deduction", [
            'description' => 'Potongan uji', 'amount' => 25000,
        ])->assertStatus(201);

        $detail = PayrollDetail::where('payroll_id', $payroll->id)->latest('id')->firstOrFail();

        $this->deleteJson("/payroll/detail/{$detail->id}")->assertOk();
        $this->assertNull(PayrollDetail::find($detail->id));

        $this->postJson("/payroll/{$period->id}/close")->assertOk();
        $this->postJson("/payroll/{$period->id}/reopen")->assertOk();
    }

    #[Test]
    public function test_report_exports(): void
    {
        $this->get('/reports/attendance/export?start_date=2026-08-01&end_date=2026-08-31')->assertOk();

        // The payroll recap is period-scoped, so the export needs one to exist.
        $period = PayrollPeriod::factory()->create();

        $this->get('/reports/payroll/export?period_id='.$period->id)->assertOk();
    }
}
