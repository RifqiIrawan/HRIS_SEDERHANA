<?php

namespace App\Http\Requests;

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
            // Checked against the masters rather than a constant, and without
            // an "only ACTIVE" filter: an employee already carrying a value
            // that was since deactivated must still be editable and savable.
            'employment_status' => ['required', 'string', 'max:20', Rule::exists('employment_statuses', 'code')],
            'employment_type' => ['required', 'string', 'max:20', Rule::exists('employment_types', 'code')],
            'join_date' => ['nullable', 'date'],
            'daily_rate' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'status' => ['required', 'string', 'max:20', Rule::exists('employee_statuses', 'code')],
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
