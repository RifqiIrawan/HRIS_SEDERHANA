<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use Illuminate\Validation\Rule;

/**
 * Spec §18 — the referenced rows must all be active, and one employee cannot
 * hold two overlapping assignments.
 */
class AssignmentRequest extends BaseRequest
{
    protected function nullableFields(): array
    {
        return ['end_date'];
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('status', 'ACTIVE'),
            ],
            'location_id' => [
                'required',
                Rule::exists('locations', 'id')->where('status', 'ACTIVE'),
            ],
            'shift_id' => [
                'required',
                Rule::exists('shifts', 'id')->where('status', 'ACTIVE'),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in([Assignment::ACTIVE, Assignment::INACTIVE])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $current = $this->route('assignment');

            $clash = Assignment::query()
                ->where('employee_id', $this->input('employee_id'))
                ->where('status', Assignment::ACTIVE)
                ->when($current, fn ($q) => $q->whereKeyNot($current->id))
                ->overlapping($this->input('start_date'), $this->input('end_date'))
                ->with('location')
                ->first();

            if ($clash) {
                $validator->errors()->add('start_date', sprintf(
                    'Bentrok dengan assignment aktif di %s (%s s/d %s).',
                    $clash->location->location_name,
                    $clash->start_date->format('d-m-Y'),
                    $clash->end_date?->format('d-m-Y') ?? 'seterusnya',
                ));
            }
        });
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Karyawan tidak ditemukan atau tidak aktif.',
            'location_id.exists' => 'Lokasi tidak ditemukan atau tidak aktif.',
            'shift_id.exists' => 'Shift tidak ditemukan atau tidak aktif.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'Karyawan',
            'location_id' => 'Lokasi',
            'shift_id' => 'Shift',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
            'status' => 'Status',
        ];
    }
}
