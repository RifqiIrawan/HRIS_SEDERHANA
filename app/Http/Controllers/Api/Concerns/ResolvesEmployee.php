<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Exceptions\AttendanceException;
use App\Models\Employee;
use Illuminate\Http\Request;

/**
 * The employee behind the token.
 *
 * Every mobile endpoint is self-service, so the personnel record always comes
 * from the authenticated user and never from a request field — the same rule
 * AC-002 states for the web check-in. Keeping it in one place means no endpoint
 * can be added later that quietly forgets it.
 */
trait ResolvesEmployee
{
    protected function currentEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            throw AttendanceException::notAnEmployee();
        }

        return $employee;
    }
}
