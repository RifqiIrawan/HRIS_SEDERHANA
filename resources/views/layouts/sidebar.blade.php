@php
    $user = auth()->user();

    // Spec §6 & §7 — the tree is the role → menu mapping in the database, not a
    // list written here. MenuAccessService is the same object the route
    // middleware consults, so a visible entry is always a reachable one.
    $menu = app(\App\Services\MenuAccessService::class)->treeFor($user);
@endphp

<aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar">
    <div class="offcanvas-header border-bottom d-lg-none">
        <x-brand />
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar"></button>
    </div>

    <div class="offcanvas-body flex-column p-0">
        <div class="sidebar-brand d-none d-lg-flex">
            <x-brand :tagline="true" />
        </div>

        <nav class="sidebar-nav flex-grow-1">
            @foreach ($menu as $group)
                {{-- Keyed by heading, not loop index: the menu is role-filtered, so an
                     index would point at a different group for ADMIN than for HR and the
                     stored open/closed state would land on the wrong section. --}}
                @php($groupId = 'sidebarGroup-' . \Illuminate\Support\Str::slug($group['heading'] ?? 'utama'))

                @if ($group['heading'])
                    <button class="sidebar-heading sidebar-heading-toggle" type="button"
                            data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}"
                            aria-expanded="true" aria-controls="{{ $groupId }}">
                        <span>{{ $group['heading'] }}</span>
                        <i class="bi bi-chevron-down sidebar-caret"></i>
                    </button>
                @endif

                {{-- The headingless first group (Dashboard, Absensi Saya) has no toggle,
                     so it is never wrapped in a collapse â€” it must always stay visible. --}}
                <div class="{{ $group['heading'] ? 'collapse show' : '' }}" id="{{ $groupId }}">
                    <ul class="nav flex-column">
                        @foreach ($group['items'] as $item)
                            @php($active = request()->routeIs($item->route_name))
                            <li class="nav-item">
                                {{-- The title is what names the entry once the rail is
                                     collapsed and the label beside the icon is gone. --}}
                                <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($item->route_name) }}"
                                   title="{{ $item->menu_name }}">
                                    <i class="bi bi-{{ $item->icon }}"></i>
                                    <span>{{ $item->menu_name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="sidebar-footer">
            <div class="small text-body-secondary">
                <div class="fw-semibold text-body">{{ $user->name }}</div>
                <div>{{ $user->employee?->employee_code ?? $user->roleCode() }}</div>
            </div>
        </div>
    </div>
</aside>

{{-- Groups render open, then this collapses the stored-closed ones. It runs here
     rather than in app.js so the state is settled before the sidebar is painted â€”
     deferring to DOMContentLoaded would show every group open for a frame. --}}
<script>
    (function () {
        var closed;
        try {
            closed = JSON.parse(localStorage.getItem('parkops-sidebar-groups') || '[]');
        } catch (e) { return; /* unreadable storage â€” leaving everything open is fine */ }
        if (!Array.isArray(closed)) { return; }

        closed.forEach(function (id) {
            var panel = document.getElementById(id);
            if (!panel) { return; }

            // The group holding the current page stays open whatever was stored,
            // so you are never dropped on a page whose menu section is hidden.
            if (panel.querySelector('.nav-link.active')) { return; }

            panel.classList.remove('show');
            var toggle = document.querySelector('[data-bs-target="#' + id + '"]');
            if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
        });
    })();
</script>
