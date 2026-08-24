<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeStatusRequest;
use App\Models\EmployeeStatus;
use Illuminate\Http\JsonResponse;

/** Master Status Karyawan — the list behind employees.status. */
class EmployeeStatusController extends ReferenceController
{
    public function store(EmployeeStatusRequest $request): JsonResponse
    {
        return $this->persist($request);
    }

    public function update(EmployeeStatusRequest $request, string $reference): JsonResponse
    {
        return $this->modify($request, $reference);
    }

    protected function modelClass(): string
    {
        return EmployeeStatus::class;
    }

    protected function routeName(): string
    {
        return 'employee-statuses';
    }

    protected function auditKey(): string
    {
        return 'employee_status';
    }

    protected function wording(): array
    {
        return [
            'title' => 'Status Karyawan',
            'subtitle' => 'Pilihan status aktif/nonaktif pada data Karyawan',
            'entity' => 'status karyawan',
            'note' => 'Dipakai oleh kolom <strong>Status</strong> di form Karyawan. '
                .'Hanya karyawan berstatus <code>ACTIVE</code> yang muncul di assignment, roster dan absensi — '
                .'status baru apa pun diperlakukan sebagai tidak aktif. '
                .'Karena itu ACTIVE, INACTIVE dan RESIGNED ditandai sebagai baris sistem dan tidak bisa dihapus.',
        ];
    }
}
