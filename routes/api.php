<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\AttendancePhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API routes — HRIS-PARKIR-MOBILE
|--------------------------------------------------------------------------
|
| Token auth (Sanctum), stateless, no CSRF and no session. Sanctum is used
| rather than JWT because a deactivated account, a resignation or a lost phone
| all have to take effect immediately, and a token row can be deleted where a
| signed JWT cannot be recalled.
|
| These are employee self-service endpoints only. The whole administrative side
| — master data, roster planning, payroll processing, HR monitoring — stays on
| the web routes and is deliberately unreachable from here.
|
| Note what is absent: the `menu` middleware. It maps a route name to a menu row
| and answers "may this role open this screen?", which is the right question for
| a sidebar and the wrong one for a personal app — there are no screens to grant
| here. What replaces it is stricter, not looser: every endpoint below resolves
| its subject from the token's own employee record (Concerns\ResolvesEmployee)
| and nothing accepts an employee id from the caller, so a token can only ever
| read the data of the person holding it.
|
*/

Route::post('/login', [AuthController::class, 'login'])
    // Coarser than the per-account throttle inside the controller: this one is
    // about a single IP hammering the endpoint across many accounts.
    ->middleware('throttle:20,1')
    ->name('api.login');

Route::middleware(['auth:sanctum', 'active'])->name('api.')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::put('/me/password', [AuthController::class, 'updatePassword'])->name('me.password');

    /*
    |----------------------------------------------------------------------
    | Attendance
    |----------------------------------------------------------------------
    */
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // Static segments before /{attendance}, or the wildcard swallows them.
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/history', [AttendanceController::class, 'history'])->name('history');
        Route::get('/summary', [AttendanceController::class, 'summary'])->name('summary');

        // The web controller unchanged: it already refuses a photo belonging to
        // someone else, and that check reads the user, not the guard.
        Route::get('/photo/{photo}', AttendancePhotoController::class)->name('photo');

        // Display-only reverse geocoding, throttled because the handset calls it
        // as the GPS reading drifts and the upstream provider is rate-limited.
        Route::get('/geocode', [AttendanceController::class, 'geocode'])
            ->middleware('throttle:30,1')
            ->name('geocode');

        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');

        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
    });

    /*
    |----------------------------------------------------------------------
    | Schedule & payslips
    |----------------------------------------------------------------------
    */
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

    Route::get('/payroll/slips', [PayrollController::class, 'index'])->name('payroll.slips.index');
    Route::get('/payroll/slips/{payroll}', [PayrollController::class, 'show'])->name('payroll.slips.show');
});
