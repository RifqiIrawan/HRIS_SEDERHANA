<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

/** Spec §14 — the three-shift pattern the MVP is built around. */
class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['S1', 'Shift 1 Pagi', '06:00:00', '14:00:00', false],
            ['S2', 'Shift 2 Siang', '14:00:00', '22:00:00', false],
            // Spec §17 — the only cross-day shift: 22:00 today → 06:00 tomorrow.
            ['S3', 'Shift 3 Malam', '22:00:00', '06:00:00', true],
        ];

        foreach ($shifts as [$code, $name, $start, $end, $crossDay]) {
            Shift::updateOrCreate(
                ['shift_code' => $code],
                [
                    'shift_name' => $name,
                    'start_time' => $start,
                    'end_time' => $end,
                    'cross_day' => $crossDay,
                    'late_tolerance_minutes' => config('parkops.default_late_tolerance_minutes'),
                    'status' => 'ACTIVE',
                ],
            );
        }
    }
}
