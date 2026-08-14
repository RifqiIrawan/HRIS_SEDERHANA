<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Spec §11 & §47 (Employee). */
class EmployeeController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('employees.index');
        }

        $employees = Employee::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term)
                    ->orWhere('nik', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'employee_code' => 'employee_code',
                'full_name' => 'full_name',
                'nik' => 'nik',
                'phone' => 'phone',
                'employment_type' => 'employment_type',
                'join_date' => 'join_date',
                'daily_rate' => 'daily_rate',
                'status' => 'status',
            ], 'employee_code'))
            ->paginate($this->perPage($request));

        return $this->paginated($employees, fn (Employee $e) => $this->transform($e));
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        AuditLog::record('employee.created', $employee, 'Karyawan '.$employee->employee_code.' dibuat');

        return $this->ok($this->transform($employee), 'Data karyawan berhasil disimpan.', 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        return $this->ok($this->transform($employee, detailed: true));
    }

    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());

        AuditLog::record('employee.updated', $employee, 'Karyawan '.$employee->employee_code.' diperbarui');

        return $this->ok($this->transform($employee), 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee): JsonResponse
    {
        // Attendance and payroll rows are the record of what was actually paid,
        // so an employee with history is deactivated rather than deleted.
        if ($employee->attendances()->exists() || $employee->payrolls()->exists()) {
            $employee->update(['status' => Employee::INACTIVE]);

            AuditLog::record('employee.deactivated', $employee, 'Karyawan dinonaktifkan (punya riwayat absensi/payroll)');

            return $this->ok(
                $this->transform($employee),
                'Karyawan memiliki riwayat absensi/payroll, sehingga dinonaktifkan alih-alih dihapus.',
            );
        }

        $code = $employee->employee_code;
        $employee->delete();

        AuditLog::record('employee.deleted', null, 'Karyawan '.$code.' dihapus');

        return $this->ok(message: 'Data karyawan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Employee $employee, bool $detailed = false): array
    {
        $base = [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'nik' => $employee->nik,
            'phone' => $employee->phone,
            'employment_status' => $employee->employment_status,
            'employment_type' => $employee->employment_type,
            'join_date' => $employee->join_date?->toDateString(),
            'daily_rate' => (float) $employee->daily_rate,
            'status' => $employee->status,
        ];

        if (! $detailed) {
            return $base;
        }

        return $base + [
            'gender' => $employee->gender,
            'birth_place' => $employee->birth_place,
            'birth_date' => $employee->birth_date?->toDateString(),
            'address' => $employee->address,
        ];
    }
}
