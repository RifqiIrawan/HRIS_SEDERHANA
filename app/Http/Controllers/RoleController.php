<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Spec §10. */
class RoleController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('roles.index', [
                'accessRoles' => $this->accessRoles(),
                'accessMenus' => $this->accessMenus(),
            ]);
        }

        $roles = Role::query()
            ->withCount('users')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('role_code', 'like', $term)
                    ->orWhere('role_name', 'like', $term));
            })
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'role_code' => 'role_code',
                'role_name' => 'role_name',
                'status' => 'status',
            ], 'role_code'))
            ->paginate($this->perPage($request));

        return $this->paginated($roles, fn (Role $r) => $this->transform($r));
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = Role::create($request->validated());

        AuditLog::record('role.created', $role, 'Role '.$role->role_code.' dibuat');

        return $this->ok($this->transform($role), 'Role berhasil disimpan.', 201);
    }

    public function show(Role $role): JsonResponse
    {
        return $this->ok($this->transform($role));
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        // The three built-in codes are wired into the authorisation checks, so
        // renaming one would silently strip access from everyone holding it.
        if (in_array($role->role_code, Role::CODES, true)
            && $request->input('role_code') !== $role->role_code) {
            return $this->fail('Kode role bawaan sistem tidak dapat diubah.', 422, 'SYSTEM_ROLE');
        }

        $role->update($request->validated());

        AuditLog::record('role.updated', $role, 'Role '.$role->role_code.' diperbarui');

        return $this->ok($this->transform($role), 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->role_code, Role::CODES, true)) {
            return $this->fail('Role bawaan sistem tidak dapat dihapus.', 422, 'SYSTEM_ROLE');
        }

        if ($role->users()->exists()) {
            return $this->fail('Role masih dipakai oleh user aktif.', 422, 'ROLE_IN_USE');
        }

        $code = $role->role_code;
        $role->delete();

        AuditLog::record('role.deleted', null, 'Role '.$code.' dihapus');

        return $this->ok(message: 'Role berhasil dihapus.');
    }

    /** The matrix as data, for a client that would rather rebuild it than reload. */
    public function menuAccess(): JsonResponse
    {
        return $this->ok([
            'roles' => $this->accessRoles(),
            'menus' => $this->accessMenus(),
        ]);
    }

    /**
     * Replaces the whole mapping in one transaction.
     *
     * The form posts the complete matrix, so an absent pair is a revocation —
     * saving a partial payload would silently keep access the administrator had
     * just unticked.
     */
    public function updateMenuAccess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'access' => ['present', 'array'],
            'access.*' => ['array'],
            'access.*.*' => ['integer', 'exists:roles,id'],
        ]);

        $submitted = $validated['access'];
        $adminRoleId = Role::where('role_code', Role::ADMIN)->value('id');
        $menus = Menu::all();

        DB::transaction(function () use ($menus, $submitted, $adminRoleId) {
            foreach ($menus as $menu) {
                $roleIds = array_values(array_unique(
                    array_map('intval', $submitted[$menu->id] ?? []),
                ));

                // A locked menu keeps ADMIN whatever the form said. User and Role
                // are the only screens that can hand access back, so letting them
                // be unmapped would be an unrecoverable lockout.
                if ($menu->is_locked && $adminRoleId && ! in_array($adminRoleId, $roleIds, true)) {
                    $roleIds[] = $adminRoleId;
                }

                $menu->roles()->sync($roleIds);
            }
        });

        AuditLog::record('menu_access.updated', null, 'Pemetaan akses menu diperbarui');

        return $this->ok(message: 'Akses menu berhasil disimpan.');
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function accessRoles()
    {
        return Role::orderBy('role_code')->get()->map(fn (Role $role) => [
            'id' => $role->id,
            'role_code' => $role->role_code,
            'role_name' => $role->role_name,
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function accessMenus()
    {
        return Menu::with('roles:id')->orderBy('sort_order')->get()->map(fn (Menu $menu) => [
            'id' => $menu->id,
            'menu_code' => $menu->menu_code,
            'menu_name' => $menu->menu_name,
            'group_name' => $menu->group_name,
            'is_locked' => $menu->is_locked,
            'role_ids' => $menu->roles->pluck('id')->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Role $role): array
    {
        return [
            'id' => $role->id,
            'role_code' => $role->role_code,
            'role_name' => $role->role_name,
            'status' => $role->status,
            'users_count' => $role->users_count ?? $role->users()->count(),
            'is_system' => in_array($role->role_code, Role::CODES, true),
        ];
    }
}
