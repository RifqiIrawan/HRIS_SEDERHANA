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
     * Replaces one role's menu list.
     *
     * Scoped to a single role rather than the whole matrix, and that is the
     * point: the screen edits one role at a time, so it stays the same width
     * whether there are three roles or fifty — and two administrators working
     * on different roles can no longer overwrite each other, which posting the
     * complete grid made unavoidable.
     *
     * Within that role the payload is still complete: an absent menu is a
     * revocation, so sending only the ticked boxes is exactly right.
     */
    public function updateMenuAccess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'menus' => ['present', 'array'],
            'menus.*' => ['integer', 'exists:menus,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $menuIds = array_values(array_unique(array_map('intval', $validated['menus'])));

        // A locked menu keeps ADMIN whatever the form said. User and Role are
        // the only screens that can hand access back, so letting them be
        // unmapped would be an unrecoverable lockout.
        if ($role->role_code === Role::ADMIN) {
            $menuIds = array_values(array_unique(array_merge(
                $menuIds,
                Menu::where('is_locked', true)->pluck('id')->all(),
            )));
        }

        // sync() is a detach followed by an attach; a failure between the two
        // would leave the role holding nothing at all.
        DB::transaction(fn () => $role->menus()->sync($menuIds));

        AuditLog::record(
            'menu_access.updated',
            $role,
            'Akses menu role '.$role->role_code.' diperbarui',
            ['menus' => count($menuIds)],
        );

        return $this->ok(
            ['role_id' => $role->id, 'menus' => $menuIds],
            'Akses menu '.$role->role_code.' berhasil disimpan.',
        );
    }

    /**
     * Every role, active first — an inactive one still has to be reachable or
     * its mapping could never be corrected before switching it back on.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function accessRoles()
    {
        return Role::orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Role::ACTIVE])
            ->orderBy('role_code')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'role_code' => $role->role_code,
                'role_name' => $role->role_name,
                'status' => $role->status,
                'is_system' => in_array($role->role_code, Role::CODES, true),
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
            'is_action' => $menu->is_action,
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
