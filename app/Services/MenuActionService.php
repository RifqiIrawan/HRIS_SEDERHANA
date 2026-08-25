<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * What a menu can grant, and which of those a request is asking for.
 *
 * The action list is derived from the routes each menu already claims rather
 * than stored beside them: a menu offers "delete" precisely when something it
 * governs answers DELETE. Written down separately the two would drift, and the
 * drift would be silent — a toggle for a verb no route serves looks enforced
 * and is not, which is worse than not offering it.
 */
class MenuActionService
{
    public const READ = 'read';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    /**
     * Verb to action. PATCH joins PUT because the application uses them
     * interchangeably for edits and an administrator should not have to know
     * which one a given form happens to send.
     */
    private const BY_METHOD = [
        'GET' => self::READ,
        'POST' => self::CREATE,
        'PUT' => self::UPDATE,
        'PATCH' => self::UPDATE,
        'DELETE' => self::DELETE,
    ];

    /** Least to most destructive, so a row never lists delete before update. */
    public const ORDER = [self::READ, self::CREATE, self::UPDATE, self::DELETE];

    public const LABELS = [
        self::READ => 'read',
        self::CREATE => 'create',
        self::UPDATE => 'update',
        self::DELETE => 'delete',
    ];

    /** Route enumeration is not free and nothing changes mid-request. */
    private ?array $routesByMenu = null;

    /** The action a request is asking for, or null for a verb we do not map. */
    public function forMethod(?string $method): ?string
    {
        return self::BY_METHOD[strtoupper((string) $method)] ?? null;
    }

    /**
     * The actions a menu can offer, in read order.
     *
     * @return array<int, string>
     */
    public function availableFor(Menu $menu): array
    {
        $actions = [];

        foreach ($this->routesFor($menu) as $route) {
            foreach ($route['methods'] as $method) {
                if ($action = $this->forMethod($method)) {
                    $actions[$action] = true;
                }
            }
        }

        return array_values(array_filter(
            self::ORDER,
            fn (string $action) => isset($actions[$action]),
        ));
    }

    /**
     * The named routes a menu governs.
     *
     * @return array<int, array{name: string, methods: array<int, string>, uri: string}>
     */
    private function routesFor(Menu $menu): array
    {
        return $this->routesByMenu()[$menu->id] ?? [];
    }

    /**
     * Every named route, filed under the menu whose claim on it is the most
     * specific — the same rule the middleware uses, read from the same
     * Menu::matchScore, so what this screen draws cannot diverge from what is
     * actually enforced.
     *
     * @return array<int, array<int, array<string, mixed>>> keyed by menu id
     */
    private function routesByMenu(): array
    {
        if ($this->routesByMenu !== null) {
            return $this->routesByMenu;
        }

        $menus = $this->menus();
        $byMenu = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            // Unnamed routes cannot be mapped, and the ungoverned ones are open
            // to every signed-in user — neither belongs to a menu's toggles.
            if ($name === null || in_array($name, MenuAccessService::UNGOVERNED, true)) {
                continue;
            }

            $best = null;
            $bestScore = -1;

            foreach ($menus as $menu) {
                $score = $menu->matchScore($name);

                if ($score !== null && $score > $bestScore) {
                    $best = $menu;
                    $bestScore = $score;
                }
            }

            if ($best === null) {
                continue;
            }

            $byMenu[$best->id][] = [
                'name' => $name,
                'methods' => array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS'])),
                'uri' => '/'.ltrim($route->uri(), '/'),
            ];
        }

        return $this->routesByMenu = $byMenu;
    }

    /** @return Collection<int, Menu> */
    private function menus(): Collection
    {
        return Menu::orderBy('sort_order')->get();
    }
}
