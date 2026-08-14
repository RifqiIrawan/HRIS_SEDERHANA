<?php

namespace App\Http\Requests;

use Illuminate\Support\Carbon;

/**
 * Spec §16 — the pattern arrives as a comma-separated string ("1,2,3,OFF")
 * and is normalised into an array of upper-case tokens here.
 */
class RosterGenerateRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $pattern = $this->input('pattern');

        if (is_string($pattern)) {
            $pattern = array_values(array_filter(
                array_map(fn ($token) => strtoupper(trim($token)), explode(',', $pattern)),
                fn ($token) => $token !== '',
            ));
        }

        $this->merge([
            'pattern' => $pattern,
            'overwrite' => $this->boolean('overwrite'),
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1', 'max:500'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'start_date' => ['required', 'date'],
            // A year at a time is plenty and keeps one careless request from
            // generating hundreds of thousands of rows.
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:'.$this->maxEndDate()],
            'pattern' => ['required', 'array', 'min:1', 'max:31'],
            'pattern.*' => ['string', 'max:30'],
            'overwrite' => ['boolean'],
        ];
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
            'pattern.required' => 'Pola shift wajib diisi, misalnya 1,2,3,OFF.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_ids' => 'Karyawan',
            'location_id' => 'Lokasi',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
            'pattern' => 'Pola Shift',
        ];
    }
}
