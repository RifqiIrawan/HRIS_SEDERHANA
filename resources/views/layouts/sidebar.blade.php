@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $isHr = $user->isHr();

    // Spec §6 & §7 — one menu tree, filtered by role. HR sees operational
    // master data but not the user/role tables; EMPLOYEE only sees itself.
    $menu = array_values(array_filter([
        [
            'heading' => null,
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'dashboard', 'show' => true],
                ['label' => 'Absensi Saya', 'icon' => 'geo-alt', 'route' => 'attendance.index', 'show' => ! $isHr],
            ],
        ],
        [
            'heading' => 'Master Data',
            'items' => [
                ['label' => 'User', 'icon' => 'person-gear', 'route' => 'users.index', 'show' => $isAdmin],
                ['label' => 'Role', 'icon' => 'shield-lock', 'route' => 'roles.index', 'show' => $isAdmin],
                ['label' => 'Karyawan', 'icon' => 'people', 'route' => 'employees.index', 'show' => $isHr],
                ['label' => 'Lokasi', 'icon' => 'pin-map', 'route' => 'locations.index', 'show' => $isHr],
                ['label' => 'Shift', 'icon' => 'clock-history', 'route' => 'shifts.index', 'show' => $isHr],
                ['label' => 'Assignment', 'icon' => 'diagram-3', 'route' => 'assignments.index', 'show' => $isHr],
            ],
        ],
        [
            'heading' => 'Jadwal',
            'items' => [
                ['label' => 'Shift Roster', 'icon' => 'calendar-week', 'route' => 'rosters.index', 'show' => $isHr],
            ],
        ],
        [
            'heading' => 'Absensi',
            'items' => [
                ['label' => 'Monitoring Absensi', 'icon' => 'activity', 'route' => 'attendance.monitoring', 'show' => $isHr],
                ['label' => 'Riwayat Absensi', 'icon' => 'journal-text', 'route' => 'attendance.history', 'show' => true],
            ],
        ],
        [
            'heading' => 'Payroll',
            'items' => [
                ['label' => 'Periode Payroll', 'icon' => 'calendar2-range', 'route' => 'payroll.periods', 'show' => $isHr],
                ['label' => 'Proses & Daftar Payroll', 'icon' => 'cash-stack', 'route' => 'payroll.index', 'show' => $isHr],
            ],
        ],
        [
            'heading' => 'Laporan',
            'items' => [
                ['label' => 'Laporan Absensi', 'icon' => 'file-earmark-bar-graph', 'route' => 'reports.attendance', 'show' => $isHr],
                ['label' => 'Laporan Payroll', 'icon' => 'file-earmark-spreadsheet', 'route' => 'reports.payroll', 'show' => $isHr],
            ],
        ],
    ], fn ($group) => collect($group['items'])->contains('show', true)));
@endphp

<aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar">
    <div class="offcanvas-header border-bottom d-lg-none">
        <span class="fw-semibold">{{ config('app.name') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar"></button>
    </div>

    <div class="offcanvas-body flex-column p-0">
        <div class="sidebar-brand d-none d-lg-flex">
            <i class="bi bi-p-square-fill"></i>
            <span>
                <strong>HRIS</strong>
                <small>Juru Parkir</small>
            </span>
        </div>

        <nav class="sidebar-nav flex-grow-1">
            @foreach ($menu as $group)
                @if ($group['heading'])
                    <div class="sidebar-heading">{{ $group['heading'] }}</div>
                @endif
                <ul class="nav flex-column">
                    @foreach ($group['items'] as $item)
                        @continue(! $item['show'])
                        @php($active = request()->routeIs($item['route']))
                        <li class="nav-item">
                            <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                <i class="bi bi-{{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
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
