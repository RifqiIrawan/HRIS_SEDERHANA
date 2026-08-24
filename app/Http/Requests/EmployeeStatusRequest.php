<?php

namespace App\Http\Requests;

class EmployeeStatusRequest extends ReferenceRequest
{
    protected function table(): string
    {
        return 'employee_statuses';
    }
}
