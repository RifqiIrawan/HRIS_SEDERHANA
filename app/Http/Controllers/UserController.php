<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Spec §9. */
class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('users.index');
        }

        $users = User::query()
            ->with(['role:id,role_code,role_name', 'employee:id,employee_code,full_name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->integer('role_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'name' => 'name',
                'email' => 'email',
                'status' => 'status',
                'last_login_at' => 'last_login_at',
            ], 'name'))
            ->paginate($this->perPage($request));

        return $this->paginated($users, fn (User $u) => $this->transform($u));
    }

    public function store(UserRequest $request): JsonResponse
    {
        // The 'hashed' cast on the model turns this into a bcrypt hash on save
        // (spec §9 — never a plain-text password).
        $user = User::create($request->validated())->load(['role', 'employee']);

        AuditLog::record('user.created', $user, 'User '.$user->email.' dibuat');

        return $this->ok($this->transform($user), 'User berhasil disimpan.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->ok($this->transform($user->load(['role', 'employee'])));
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        // A blank password field on the edit form means "keep the current one".
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);
        $user->load(['role', 'employee']);

        AuditLog::record('user.updated', $user, 'User '.$user->email.' diperbarui');

        return $this->ok($this->transform($user), 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return $this->fail('Anda tidak dapat menghapus akun Anda sendiri.', 422, 'SELF_DELETE');
        }

        if ($this->isLastActiveAdmin($user)) {
            return $this->fail('Minimal harus ada satu user ADMIN yang aktif.', 422, 'LAST_ADMIN');
        }

        $email = $user->email;
        $user->delete();

        AuditLog::record('user.deleted', null, 'User '.$email.' dihapus');

        return $this->ok(message: 'User berhasil dihapus.');
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return ! User::query()
            ->whereKeyNot($user->id)
            ->where('status', User::ACTIVE)
            ->whereHas('role', fn ($q) => $q->where('role_code', 'ADMIN'))
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role_code' => $user->role?->role_code,
            'role_name' => $user->role?->role_name,
            'employee_id' => $user->employee_id,
            'employee_label' => $user->employee
                ? $user->employee->employee_code.' — '.$user->employee->full_name
                : null,
            'status' => $user->status,
            'last_login_at' => $user->last_login_at?->format('d M Y H:i'),
        ];
    }
}
