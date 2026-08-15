<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The single place that answers "may this user reach this?".
 *
 * Both the sidebar and the route middleware ask this service, so a menu can
 * never be visible without being reachable, or reachable without being visible.
 */
class MenuAccessService
{
    /**
     * Routes deliberately outside the mapping: the session and profile actions
     * every signed-in user needs, plus the shared lookup payload the forms read.
     *
     * The list is explicit because everything else is denied unless a menu
     * claims it — an unrecognised route must not be a hole, or an empty or
     * half-migrated menus table would silently open the whole application.
     */
    public const UNGOVERNED = [
        'logout',
        'profile',
        'profile.password',
        'lookups',

        // A stored file, not a screen. AttendancePhotoController decides for
        // itself who may see a photo — the owner or an HR reviewer working the
        // monitoring list — and HR has no employee row to satisfy a menu rule.
        'attendance.photo',
    ];

    /** Menus are read on nearly every request; load them once per request. */
    private ?Collection $menus = null;

    /** @return Collection<int, Menu> */
    public function all(): Collection
    {
        return $this->menus ??= Menu::active()
            ->with('roles:id')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Menus this user may reach, in sidebar order.
     *
     * @return Collection<int, Menu>
     */
    public function forUser(User $user): Collection
    {
        return $this->all()->filter(fn (Menu $menu) => $this->visible($menu, $user))->values();
    }

    /**
     * The sidebar tree: menus grouped by heading, groups in first-appearance
     * order, headings preserved as written on the rows.
     *
     * @return array<int, array{heading: ?string, items: Collection<int, Menu>}>
     */
    public function treeFor(User $user): array
    {
        return $this->forUser($user)
            ->groupBy(fn (Menu $menu) => $menu->group_name ?? '')
            ->map(fn (Collection $items, string $heading) => [
                'heading' => $heading === '' ? null : $heading,
                'items' => $items,
            ])
            ->values()
            ->all();
    }

    /**
     * Whether a route name is reachable by this user.
     *
     * Denies by default: only the UNGOVERNED list and routes a menu actually
     * claims get through. An unnamed route cannot be mapped, so it is refused
     * rather than waved past.
     */
    public function allowsRoute(User $user, ?string $routeName): bool
    {
        if ($routeName !== null && in_array($routeName, self::UNGOVERNED, true)) {
            return true;
        }

        if ($routeName === null) {
            return false;
        }

        $menu = $this->menuForRoute($routeName);

        return $menu !== null && $this->grants($menu, $user);
    }

    /**
     * The menu governing a route, or null. The most specific claim wins, so a
     * menu naming the exact route beats one matching it by wildcard.
     */
    public function menuForRoute(string $routeName): ?Menu
    {
        $best = null;
        $bestScore = -1;

        foreach ($this->all() as $menu) {
            $score = $menu->matchScore($routeName);

            if ($score !== null && $score > $bestScore) {
                $best = $menu;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * The mapping decision: may this role reach this menu at all?
     *
     * One rule sits above the table — a locked menu stays open to ADMIN, so the
     * screen that edits the mapping can never be mapped away.
     */
    private function grants(Menu $menu, User $user): bool
    {
        if ($menu->is_locked && $user->isAdmin()) {
            return true;
        }

        return $menu->roles->contains('id', $user->role_id);
    }

    /**
     * Whether the entry belongs in this user's sidebar.
     *
     * requires_employee is a visibility rule, deliberately not an access one.
     * The check-in screen resolves its employee from users.employee_id and the
     * controller refuses without it, with a specific error code the frontend
     * shows — enforcing the same thing out here would replace that message with
     * a bare 403, and would also shut HR out of the attendance endpoints it is
     * supposed to reach while reviewing other people's records.
     */
    private function visible(Menu $menu, User $user): bool
    {
        if ($menu->requires_employee && $user->employee_id === null) {
            return false;
        }

        return $this->grants($menu, $user);
    }
}
