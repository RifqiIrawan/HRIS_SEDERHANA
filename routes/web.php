<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendancePhotoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes (spec §47)
|--------------------------------------------------------------------------
|
| Index routes serve a Blade page for a normal request and JSON for an AJAX
| one, which is what keeps the URL table identical to the specification while
| still giving each module a page to live on.
|
| Access follows the role → menu mapping in the database rather than gates
| written here: the `menu` middleware resolves the route name to its menu row
| and checks the mapping, so revoking a menu also closes its URLs. The seeded
| mapping reproduces spec §7 exactly — ADMIN everything, HR the operational
| modules, EMPLOYEE only its own attendance and profile.
|
| Routes no menu claims (logout, profile, lookups) stay open to any signed-in
| user. `role:` remains for the few checks that are about an action rather than
| a menu, such as reopening a closed payroll period.
|
*/

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active', 'menu'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shared select-option payload, so a page with four dropdowns costs one
    // request instead of four.
    Route::get('/lookups', LookupController::class)->name('lookups');

    /*
    |----------------------------------------------------------------------
    | Attendance — spec §19-§33
    |----------------------------------------------------------------------
    */
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // Static segments must be declared before /{attendance}.
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');
        Route::get('/monitoring', [AttendanceController::class, 'monitoring'])->name('monitoring');
        Route::get('/photo/{photo}', AttendancePhotoController::class)->name('photo');

        // Display-only reverse geocoding for the check-in screen. Throttled
        // because the browser calls it as the GPS reading drifts, and the
        // upstream provider is rate-limited.
        Route::get('/geocode', [AttendanceController::class, 'geocode'])
            ->middleware('throttle:30,1')
            ->name('geocode');

        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');

        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
    });

    /*
    |----------------------------------------------------------------------
    | Master data — spec §9-§18
    |----------------------------------------------------------------------
    */
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');

    // Declared before /roles/{role}, or the wildcard swallows "menu-access".
    Route::get('/roles/menu-access', [RoleController::class, 'menuAccess'])->name('roles.menu-access');
    Route::put('/roles/menu-access', [RoleController::class, 'updateMenuAccess'])->name('roles.menu-access.update');

    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
    Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');
    Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
    Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    /*
    |----------------------------------------------------------------------
    | Roster — spec §15-§17
    |----------------------------------------------------------------------
    */
    Route::get('/rosters', [RosterController::class, 'index'])->name('rosters.index');
    Route::post('/rosters/generate', [RosterController::class, 'generate'])->name('rosters.generate');
    Route::post('/rosters/preview', [RosterController::class, 'preview'])->name('rosters.preview');
    Route::get('/rosters/{roster}', [RosterController::class, 'show'])->name('rosters.show');
    Route::put('/rosters/{roster}', [RosterController::class, 'update'])->name('rosters.update');
    Route::delete('/rosters/{roster}', [RosterController::class, 'destroy'])->name('rosters.destroy');

    /*
    |----------------------------------------------------------------------
    | Payroll — spec §36-§44
    |----------------------------------------------------------------------
    */
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/periods', [PayrollController::class, 'periods'])->name('periods');
        Route::post('/periods', [PayrollController::class, 'storePeriod'])->name('periods.store');
        Route::get('/periods/{period}', [PayrollController::class, 'showPeriod'])->name('periods.show');
        Route::put('/periods/{period}', [PayrollController::class, 'updatePeriod'])->name('periods.update');
        Route::delete('/periods/{period}', [PayrollController::class, 'destroyPeriod'])->name('periods.destroy');

        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::delete('/detail/{detail}', [PayrollController::class, 'destroyDeduction'])->name('detail.destroy');

        Route::post('/{period}/generate', [PayrollController::class, 'generate'])->name('generate');
        Route::post('/{period}/close', [PayrollController::class, 'close'])->name('close');

        // An action gate, not a menu one: reopening is restricted even for the
        // roles that may otherwise work the payroll screen (spec §7).
        Route::post('/{period}/reopen', [PayrollController::class, 'reopen'])
            ->middleware('role:'.Role::ADMIN)->name('reopen');

        Route::get('/{period}', [PayrollController::class, 'show'])->name('show');
        Route::post('/{payroll}/deduction', [PayrollController::class, 'storeDeduction'])->name('deduction');
    });

    /*
    |----------------------------------------------------------------------
    | Reports — spec §60 phase 6
    |----------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('/attendance/export', [ReportController::class, 'exportAttendance'])->name('attendance.export');
        Route::get('/payroll', [ReportController::class, 'payroll'])->name('payroll');
        Route::get('/payroll/export', [ReportController::class, 'exportPayroll'])->name('payroll.export');
    });
});
