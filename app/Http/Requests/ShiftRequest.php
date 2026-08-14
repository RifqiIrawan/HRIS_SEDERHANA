<?php

namespace App\Http\Requests;

use App\Models\Shift;
use Illuminate\Validation\Rule;

/** Spec §14. */
class ShiftRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'cross_day' => $this->boolean('cross_day'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('shift')?->id;

        return [
            'shift_code' => ['required', 'string', 'max:30', Rule::unique('shifts')->ignore($id)],
            'shift_name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'cross_day' => ['required', 'boolean'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'status' => ['required', Rule::in([Shift::ACTIVE, Shift::INACTIVE])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if (! $start || ! $end) {
                return;
            }

            // A shift whose end time is at or before its start time can only
            // mean it runs past midnight; leaving cross_day unticked there
            // would give the roster a negative-length shift.
            if ($end <= $start && ! $this->boolean('cross_day')) {
                $validator->errors()->add(
                    'cross_day',
                    'Jam selesai lebih awal dari jam mulai — centang "Lintas Hari" untuk shift malam.',
                );
            }

            if ($end > $start && $this->boolean('cross_day')) {
                $validator->errors()->add(
                    'cross_day',
                    'Shift ini selesai di hari yang sama, jangan centang "Lintas Hari".',
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'shift_code' => 'Kode Shift',
            'shift_name' => 'Nama Shift',
            'start_time' => 'Jam Mulai',
            'end_time' => 'Jam Selesai',
            'cross_day' => 'Lintas Hari',
            'late_tolerance_minutes' => 'Toleransi Keterlambatan',
            'status' => 'Status',
        ];
    }
}
