<?php

namespace App\Http\Requests;

class EmploymentTypeRequest extends ReferenceRequest
{
    protected function table(): string
    {
        return 'employment_types';
    }
}
