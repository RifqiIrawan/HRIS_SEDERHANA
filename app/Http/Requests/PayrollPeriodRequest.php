<?php

namespace App\Http\Requests;

use App\Models\PayrollPeriod;
use Illuminate\Validation\Rule;

/** Spec §37. */
class PayrollPeriodRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('period')?->id;

        return [
            'period_code' => ['required', 'string', 'max:30', Rule::unique('payroll_periods')->ignore($id)],
            'period_name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $current = $this->route('period');

            // Overlapping periods would count the same attendance day twice.
            $clash = PayrollPeriod::query()
                ->when($current, fn ($q) => $q->whereKeyNot($current->id))
                ->whereDate('start_date', '<=', $this->input('end_date'))
                ->whereDate('end_date', '>=', $this->input('start_date'))
                ->first();

            if ($clash) {
                $validator->errors()->add('start_date', sprintf(
                    'Rentang tanggal bertabrakan dengan periode "%s" (%s s/d %s).',
                    $clash->period_name,
                    $clash->start_date->format('d-m-Y'),
                    $clash->end_date->format('d-m-Y'),
                ));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'period_code' => 'Kode Periode',
            'period_name' => 'Nama Periode',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
        ];
    }
}
