<?php

namespace App\Http\Requests;

class EmploymentStatusRequest extends ReferenceRequest
{
    protected function table(): string
    {
        return 'employment_statuses';
    }
}
