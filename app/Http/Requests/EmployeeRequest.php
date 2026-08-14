<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Validation\Rule;

/** Spec §11. */
class EmployeeRequest extends BaseRequest
{
    protected function nullableFields(): array
    {
        return ['nik', 'gender', 'birth_place', 'birth_date', 'phone', 'address', 'join_date'];
    }

    public function rules(): array
    {
        $id = $this->route('employee')?->id;

        return [
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees')->ignore($id)],
            'nik' => ['nullable', 'string', 'max:30', Rule::unique('employees')->ignore($id)],
            'full_name' => ['required', 'string', 'max:150'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'employment_status' => ['required', Rule::in(Employee::EMPLOYMENT_STATUSES)],
            'employment_type' => ['required', Rule::in(Employee::EMPLOYMENT_TYPES)],
            'join_date' => ['nullable', 'date'],
            'daily_rate' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'status' => ['required', Rule::in(Employee::STATUSES)],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_code' => 'Kode Karyawan',
            'nik' => 'NIK',
            'full_name' => 'Nama Lengkap',
            'gender' => 'Jenis Kelamin',
            'birth_place' => 'Tempat Lahir',
            'birth_date' => 'Tanggal Lahir',
            'phone' => 'No. HP',
            'address' => 'Alamat',
            'employment_status' => 'Status Kepegawaian',
            'employment_type' => 'Tipe Kepegawaian',
            'join_date' => 'Tanggal Bergabung',
            'daily_rate' => 'Upah Harian',
            'status' => 'Status',
        ];
    }
}
