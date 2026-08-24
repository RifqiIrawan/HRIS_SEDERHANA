<?php

namespace App\Http\Requests;

use App\Models\ReferenceModel;
use Illuminate\Validation\Rule;

/**
 * Validation shared by the three Karyawan reference masters.
 *
 * The code is what employees rows actually store, so it is normalised to upper
 * case and restricted to a token shape here rather than trusted from the form —
 * "Kontrak " and "KONTRAK" must not become two different statuses.
 */
abstract class ReferenceRequest extends BaseRequest
{
    /** Table the uniqueness rule applies to. */
    abstract protected function table(): string;

    protected function nullableFields(): array
    {
        return ['description'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($code = $this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($code))]);
        }
    }

    public function rules(): array
    {
        // Routes use a plain {reference} id rather than model binding, so the
        // ignore target is the raw route parameter.
        $id = $this->route('reference');

        return [
            'code' => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique($this->table(), 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in([ReferenceModel::ACTIVE, ReferenceModel::INACTIVE])],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan garis bawah (_).',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode',
            'name' => 'Nama',
            'description' => 'Keterangan',
            'sort_order' => 'Urutan',
            'status' => 'Status',
        ];
    }
}
