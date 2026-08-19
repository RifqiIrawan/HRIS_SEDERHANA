<?php

namespace App\Http\Requests;

use App\Services\ShiftRotationService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Input for the rotating-shift generator on the Assignment screen.
 *
 * Shared by the preview and the commit so a plan cannot be approved under one
 * set of rules and written under another.
 */
class AssignmentRotationRequest extends BaseRequest
{
    protected function nullableFields(): array
    {
        return ['start_shift_id'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $weekdays = $this->input('off_weekdays', []);

        $this->merge([
            'cycle_days' => (int) ($this->input('cycle_days') ?: 7),
            'off_days_per_cycle' => (int) $this->input('off_days_per_cycle', 1),
            'direction' => strtoupper((string) ($this->input('direction') ?: ShiftRotationService::DOWN)),
            'off_day_mode' => strtoupper((string) ($this->input('off_day_mode') ?: ShiftRotationService::OFF_AUTO)),
            'off_weekdays' => array_values(array_unique(array_map('intval', (array) $weekdays))),
            'shift_ids' => array_values(array_unique(array_map('intval', (array) $this->input('shift_ids', [])))),
            'replace' => $this->boolean('replace'),
            'with_roster' => $this->boolean('with_roster'),
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1', 'max:500'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('status', 'ACTIVE')],
            'start_date' => ['required', 'date'],
            // A year at a time keeps one careless request from writing tens of
            // thousands of roster rows.
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:'.$this->maxEndDate()],
            'cycle_days' => ['required', 'integer', 'min:1', 'max:31'],
            'direction' => ['required', Rule::in([ShiftRotationService::DOWN, ShiftRotationService::UP])],
            'start_shift_id' => ['nullable', Rule::exists('shifts', 'id')->where('status', 'ACTIVE')],
            'off_days_per_cycle' => ['required', 'integer', 'min:0', 'max:6'],
            'off_day_mode' => ['required', Rule::in([ShiftRotationService::OFF_AUTO, ShiftRotationService::OFF_FIXED])],
            'off_weekdays' => ['array', 'max:6'],
            'off_weekdays.*' => ['integer', 'min:1', 'max:7'],
            // Empty means "every active shift"; a partial selection has to
            // leave something to rotate between.
            'shift_ids' => ['array'],
            'shift_ids.*' => [Rule::exists('shifts', 'id')->where('status', 'ACTIVE')],
            'replace' => ['boolean'],
            'with_roster' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // A cycle of rest days only would schedule nobody.
            if ($this->integer('off_days_per_cycle') >= $this->integer('cycle_days')) {
                $validator->errors()->add(
                    'off_days_per_cycle',
                    'Hari libur harus lebih sedikit daripada panjang siklus.',
                );
            }

            $shiftIds = $this->input('shift_ids');

            if (is_array($shiftIds) && count($shiftIds) === 1) {
                $validator->errors()->add('shift_ids', 'Pilih minimal 2 shift untuk dirotasi.');
            }

            // In FIXED mode the weekdays are the whole instruction; without
            // them the generator would quietly produce no rest days at all.
            if ($this->input('off_day_mode') === ShiftRotationService::OFF_FIXED
                && $this->integer('off_days_per_cycle') > 0
                && $this->input('off_weekdays') === []) {
                $validator->errors()->add('off_weekdays', 'Pilih hari libur tetapnya.');
            }
        });
    }

    private function maxEndDate(): string
    {
        $start = $this->input('start_date');

        return $start && strtotime($start)
            ? Carbon::parse($start)->addYear()->toDateString()
            : Carbon::now()->addYear()->toDateString();
    }

    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Pilih minimal satu karyawan.',
            'end_date.before_or_equal' => 'Rentang generate maksimal 1 tahun.',
            'location_id.exists' => 'Lokasi tidak ditemukan atau tidak aktif.',
            'shift_ids.*.exists' => 'Shift yang dipilih tidak ditemukan atau tidak aktif.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_ids' => 'Karyawan',
            'location_id' => 'Lokasi',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
            'cycle_days' => 'Panjang Siklus',
            'direction' => 'Arah Rotasi',
            'start_shift_id' => 'Shift Awal',
            'off_days_per_cycle' => 'Hari Libur',
            'off_day_mode' => 'Mode Libur',
            'off_weekdays' => 'Hari Libur Tetap',
            'shift_ids' => 'Shift yang Dirotasi',
        ];
    }
}
