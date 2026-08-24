<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentTypeRequest;
use App\Models\EmploymentType;
use Illuminate\Http\JsonResponse;

/** Master Tipe Kepegawaian — the list behind employees.employment_type. */
class EmploymentTypeController extends ReferenceController
{
    public function store(EmploymentTypeRequest $request): JsonResponse
    {
        return $this->persist($request);
    }

    public function update(EmploymentTypeRequest $request, string $reference): JsonResponse
    {
        return $this->modify($request, $reference);
    }

    protected function modelClass(): string
    {
        return EmploymentType::class;
    }

    protected function routeName(): string
    {
        return 'employment-types';
    }

    protected function auditKey(): string
    {
        return 'employment_type';
    }

    protected function wording(): array
    {
        return [
            'title' => 'Tipe Kepegawaian',
            'subtitle' => 'Pilihan tipe kepegawaian pada form Karyawan',
            'entity' => 'tipe kepegawaian',
            'note' => 'Dipakai oleh kolom <strong>Tipe</strong> di form Karyawan. '
                .'Perhitungan payroll tetap memakai <strong>Upah Harian</strong> untuk semua tipe, '
                .'jadi menambah tipe baru mengubah pilihan pada form, bukan cara slip gaji dihitung.',
        ];
    }
}
