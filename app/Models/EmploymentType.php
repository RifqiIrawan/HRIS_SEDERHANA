<?php

namespace App\Models;

/** Master Tipe Kepegawaian — employees.employment_type (spec §11). */
class EmploymentType extends ReferenceModel
{
    public const EMPLOYEE_COLUMN = 'employment_type';
}
