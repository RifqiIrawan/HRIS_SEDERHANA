<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The menu registry and its default role mapping.
 *
 * The defaults reproduce the access matrix the route middleware used to
 * hard-code (spec §7), so seeding changes the mechanism without changing who
 * can reach what. From here on the mapping is data, editable at Role → Akses
 * Menu.
 */
class MenuSeeder extends Seeder
{
    /**
     * code, name, icon, group, route, patterns, requires_employee, locked, roles
     */
    private const MENUS = [
        [
            'dashboard', 'Dashboard', 'speedometer2', null, 'dashboard',
            ['dashboard'], false, false, [Role::ADMIN, Role::HR, Role::EMPLOYEE],
        ],
        [
            // Patterns are enumerated rather than "attendance.*" so the
            // monitoring and history screens stay separately governable.
            'attendance', 'Absensi Saya', 'geo-alt', null, 'attendance.index',
            [
                'attendance.index', 'attendance.check-in', 'attendance.check-out',
                'attendance.geocode', 'attendance.show',
            ],
            true, false, [Role::ADMIN, Role::HR, Role::EMPLOYEE],
        ],
        [
            'users', 'User', 'person-gear', 'Master Data', 'users.index',
            ['users.*'], false, true, [Role::ADMIN],
        ],
        [
            'roles', 'Role', 'shield-lock', 'Master Data', 'roles.index',
            ['roles.*'], false, true, [Role::ADMIN],
        ],
        [
            'employees', 'Karyawan', 'people', 'Master Data', 'employees.index',
            ['employees.*'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'locations', 'Lokasi', 'pin-map', 'Master Data', 'locations.index',
            ['locations.*'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'shifts', 'Shift', 'clock-history', 'Master Data', 'shifts.index',
            ['shifts.*'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'assignments', 'Assignment', 'diagram-3', 'Master Data', 'assignments.index',
            ['assignments.*'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'rosters', 'Shift Roster', 'calendar-week', 'Jadwal', 'rosters.index',
            ['rosters.*'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'attendance_monitoring', 'Monitoring Absensi', 'activity', 'Absensi', 'attendance.monitoring',
            ['attendance.monitoring'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'attendance_history', 'Riwayat Absensi', 'journal-text', 'Absensi', 'attendance.history',
            ['attendance.history'], false, false, [Role::ADMIN, Role::HR, Role::EMPLOYEE],
        ],
        [
            'payroll_periods', 'Periode Payroll', 'calendar2-range', 'Payroll', 'payroll.periods',
            ['payroll.periods', 'payroll.periods.*'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'payroll', 'Proses & Daftar Payroll', 'cash-stack', 'Payroll', 'payroll.index',
            [
                'payroll.index', 'payroll.show', 'payroll.generate', 'payroll.close',
                'payroll.reopen', 'payroll.deduction', 'payroll.detail.*',
            ],
            false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'reports_attendance', 'Laporan Absensi', 'file-earmark-bar-graph', 'Laporan', 'reports.attendance',
            ['reports.attendance', 'reports.attendance.export'], false, false, [Role::ADMIN, Role::HR],
        ],
        [
            'reports_payroll', 'Laporan Payroll', 'file-earmark-spreadsheet', 'Laporan', 'reports.payroll',
            ['reports.payroll', 'reports.payroll.export'], false, false, [Role::ADMIN, Role::HR],
        ],
    ];

    public function run(): void
    {
        $roleIds = Role::pluck('id', 'role_code');

        foreach (self::MENUS as $index => [
            $code, $name, $icon, $group, $route, $patterns, $requiresEmployee, $locked, $roles
        ]) {
            $menu = Menu::updateOrCreate(
                ['menu_code' => $code],
                [
                    'menu_name' => $name,
                    'icon' => $icon,
                    'group_name' => $group,
                    'route_name' => $route,
                    'route_patterns' => $patterns,
                    'requires_employee' => $requiresEmployee,
                    'is_locked' => $locked,
                    'sort_order' => ($index + 1) * 10,
                    'status' => Menu::ACTIVE,
                ],
            );

            // Defaults are applied only when the row is first created. Re-running
            // the seeder after an administrator has edited the matrix must not
            // quietly hand back access they deliberately revoked.
            if ($menu->wasRecentlyCreated) {
                $menu->roles()->sync(
                    collect($roles)->map(fn ($code) => $roleIds[$code] ?? null)->filter()->all(),
                );
            }
        }
    }
}
