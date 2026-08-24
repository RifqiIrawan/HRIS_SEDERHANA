<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentStatusRequest;
use App\Models\EmploymentStatus;
use Illuminate\Http\JsonResponse;

/** Master Status Kepegawaian — the list behind employees.employment_status. */
class EmploymentStatusController extends ReferenceController
{
    public function store(EmploymentStatusRequest $request): JsonResponse
    {
        return $this->persist($request);
    }

    public function update(EmploymentStatusRequest $request, string $reference): JsonResponse
    {
        return $this->modify($request, $reference);
    }

    protected function modelClass(): string
    {
        return EmploymentStatus::class;
    }

    protected function routeName(): string
    {
        return 'employment-statuses';
    }

    protected function auditKey(): string
    {
        return 'employment_status';
    }

    protected function wording(): array
    {
        return [
            'title' => 'Status Kepegawaian',
            'subtitle' => 'Pilihan status kepegawaian pada form Karyawan',
            'entity' => 'status kepegawaian',
            'note' => 'Dipakai oleh kolom <strong>Status Kepegawaian</strong> di form Karyawan. '
                .'Kode disimpan apa adanya pada data karyawan, jadi ubah kode hanya bila perlu — '
                .'data karyawan yang memakainya akan ikut diperbarui.',
        ];
    }
}
