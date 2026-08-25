<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\Role;
use App\Services\MenuActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Spec §10. */
class RoleController extends Controller
{
    public function __construct(private readonly MenuActionService $actions) {}

    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('roles.index', [
                'accessRoles' => $this->accessRoles(),
                'accessGroups' => $this->accessGroups(),
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
     * Replaces one role's grants.
     *
     * Scoped to a single role rather than the whole matrix, and that is the
     * point: the screen edits one role at a time, so it stays the same width
     * whether there are three roles or fifty — and two administrators working
     * on different roles can no longer overwrite each other, which posting the
     * complete grid made unavoidable.
     *
     * Within that role the payload is still complete: an absent menu is a
     * revocation, so sending only what is switched on is exactly right.
     */
    public function updateMenuAccess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'menus' => ['present', 'array'],
            'menus.*.id' => ['required', 'integer', 'exists:menus,id'],
            'menus.*.actions' => ['present', 'array'],
            'menus.*.actions.*' => ['string', Rule::in(MenuActionService::ORDER)],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $menus = Menu::whereIn('id', array_column($validated['menus'], 'id'))->get()->keyBy('id');

        $sync = [];

        foreach ($validated['menus'] as $row) {
            $menu = $menus->get((int) $row['id']);

            if ($menu === null) {
                continue;
            }

            // Clamped to what the menu can actually offer. A stored "delete" on
            // a menu no route answers DELETE for would read as a live grant on
            // the next screen draw, and would quietly become one the day such a
            // route is added.
            $granted = array_values(array_intersect(
                $this->actions->availableFor($menu),
                array_unique($row['actions']),
            ));

            // A grant is its actions. Keeping a row with none would put the
            // menu in the sidebar and then answer 403 at the door.
            if ($granted === []) {
                continue;
            }

            $sync[$menu->id] = ['actions' => $granted];
        }

        // Locked menus keep ADMIN whatever the form said, at full reach. User
        // and Role are the only screens that can hand access back, so letting
        // them be unmapped — or left read-only, which cannot save — would be an
        // unrecoverable lockout.
        if ($role->role_code === Role::ADMIN) {
            foreach (Menu::where('is_locked', true)->get() as $locked) {
                $sync[$locked->id] = ['actions' => $this->actions->availableFor($locked)];
            }
        }

        // sync() is a detach followed by an attach; a failure between the two
        // would leave the role holding nothing at all.
        DB::transaction(fn () => $role->menus()->sync($sync));

        AuditLog::record(
            'menu_access.updated',
            $role,
            'Akses menu role '.$role->role_code.' diperbarui',
            ['menus' => count($sync), 'actions' => array_sum(array_map(
                fn (array $p) => count($p['actions']),
                $sync,
            ))],
        );

        return $this->ok(
            ['role_id' => $role->id, 'menus' => $sync],
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
            'available' => $this->actions->availableFor($menu),
            'grants' => $this->grantsFor($menu),
        ]);
    }

    /**
     * The menu registry as the access editor renders it: grouped by heading in
     * sidebar order, each menu carrying the actions it can offer and, per role,
     * the ones actually held.
     *
     * @return array<int, array<string, mixed>>
     */
    private function accessGroups(): array
    {
        $menus = Menu::with('roles:id')->orderBy('sort_order')->get();
        $groups = [];

        foreach ($menus as $menu) {
            // A menu with no heading is not an error: Dashboard and the
            // check-in screen sit at the top of the sidebar ungrouped.
            $heading = $menu->group_name ?: 'Umum';

            $groups[$heading] ??= [
                'name' => $heading,
                'slug' => 'grp-'.substr(md5($heading), 0, 8),
                'menus' => [],
            ];

            $groups[$heading]['menus'][] = [
                'id' => $menu->id,
                'menu_code' => $menu->menu_code,
                'menu_name' => $menu->menu_name,
                'is_action' => $menu->is_action,
                'is_locked' => $menu->is_locked,
                'available' => $this->actions->availableFor($menu),
                'grants' => $this->grantsFor($menu),
            ];
        }

        return array_values($groups);
    }

    /**
     * Which actions each role holds on this menu, keyed by role id.
     *
     * A NULL pivot is expanded to the full set here rather than left for the
     * client to interpret: the screen would otherwise have to reimplement the
     * "NULL means everything" rule that MenuAccessService already owns, and a
     * disagreement between the two would draw toggles that do not match what
     * the middleware enforces.
     *
     * @return array<int, array<int, string>>
     */
    private function grantsFor(Menu $menu): array
    {
        $available = $this->actions->availableFor($menu);
        $grants = [];

        foreach ($menu->roles as $role) {
            $held = $role->pivot->actions;

            $grants[$role->id] = $held === null
                ? $available
                : array_values(array_intersect($available, $held));
        }

        return $grants;
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
