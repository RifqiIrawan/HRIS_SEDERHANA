<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;

/** Spec §9. */
class UserRequest extends BaseRequest
{
    protected function nullableFields(): array
    {
        return ['employee_id', 'password', 'password_confirmation'];
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $isUpdate = $user !== null;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user?->id)],
            // On update an empty password field means "leave it unchanged".
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'employee_id' => [
                'nullable',
                Rule::exists('employees', 'id'),
                Rule::unique('users', 'employee_id')->ignore($user?->id),
            ],
            'status' => ['required', Rule::in([User::ACTIVE, User::INACTIVE])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $role = Role::find($this->input('role_id'));

            // An EMPLOYEE account with no employee row could log in but would
            // have nothing to check in against (AC-002).
            if ($role?->role_code === Role::EMPLOYEE && ! $this->filled('employee_id')) {
                $validator->errors()->add('employee_id', 'Akun dengan role EMPLOYEE wajib terhubung ke data karyawan.');
            }

            $user = $this->route('user');

            // Removing the last active administrator would lock everyone out
            // of user management permanently.
            if ($user && $user->isAdmin() && $this->wouldDropLastAdmin($user, $role)) {
                $validator->errors()->add('role_id', 'Minimal harus ada satu user ADMIN yang aktif.');
            }
        });
    }

    private function wouldDropLastAdmin(User $user, ?Role $newRole): bool
    {
        $staysAdmin = $newRole?->role_code === Role::ADMIN && $this->input('status') === User::ACTIVE;

        if ($staysAdmin) {
            return false;
        }

        $otherAdmins = User::query()
            ->whereKeyNot($user->id)
            ->where('status', User::ACTIVE)
            ->whereHas('role', fn ($q) => $q->where('role_code', Role::ADMIN))
            ->count();

        return $otherAdmins === 0;
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'employee_id.unique' => 'Karyawan ini sudah memiliki akun user.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'role_id' => 'Role',
            'employee_id' => 'Karyawan',
            'status' => 'Status',
        ];
    }
}
