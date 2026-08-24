<?php

namespace App\Models;

/** Master Status Kepegawaian — employees.employment_status (spec §11). */
class EmploymentStatus extends ReferenceModel
{
    public const EMPLOYEE_COLUMN = 'employment_status';
}
