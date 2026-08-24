<?php

namespace App\Models;

/** Master Status Karyawan — employees.status (spec §11). */
class EmployeeStatus extends ReferenceModel
{
    public const EMPLOYEE_COLUMN = 'status';
}
