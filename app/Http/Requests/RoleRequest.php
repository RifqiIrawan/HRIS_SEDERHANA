<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Spec §10. */
class RoleRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('role_code')) {
            $this->merge(['role_code' => strtoupper(trim($this->input('role_code')))]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('role')?->id;

        return [
            'role_code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('roles')->ignore($id),
            ],
            'role_name' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    public function messages(): array
    {
        return [
            'role_code.regex' => 'Kode role hanya boleh huruf kapital, angka, dan garis bawah.',
        ];
    }

    public function attributes(): array
    {
        return [
            'role_code' => 'Kode Role',
            'role_name' => 'Nama Role',
            'status' => 'Status',
        ];
    }
}
