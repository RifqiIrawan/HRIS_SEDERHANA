<?php

namespace Database\Seeders;

use App\Models\EmployeeStatus;
use App\Models\EmploymentStatus;
use App\Models\EmploymentType;
use App\Models\ReferenceModel;
use Illuminate\Database\Seeder;

/**
 * The starting rows for the three Karyawan reference masters — exactly the
 * values that used to be hard-coded in the form and in Employee's constants,
 * so seeding changes where the list lives without changing what it contains.
 *
 * Rows are created, never overwritten: re-running the seeder after someone has
 * renamed "Percobaan" must not undo their edit. The is_system flag is the one
 * thing kept in step, because the guards in ReferenceController depend on it.
 */
class ReferenceSeeder extends Seeder
{
    /** code, name, description, is_system */
    private const EMPLOYMENT_STATUSES = [
        ['PERCOBAAN', 'Percobaan', 'Masa percobaan sebelum diangkat', false],
        ['KONTRAK', 'Kontrak', 'Perjanjian kerja waktu tertentu', false],
        ['TETAP', 'Tetap', 'Karyawan tetap', false],
    ];

    private const EMPLOYMENT_TYPES = [
        ['DAILY', 'Harian', 'Dibayar per hari kerja berdasarkan upah harian', false],
    ];

    /**
     * All three are flagged as system rows: Employee::ACTIVE and friends are
     * compared as literals by the assignment, roster and attendance modules,
     * so these codes cannot be renamed or removed from the master.
     */
    private const EMPLOYEE_STATUSES = [
        ['ACTIVE', 'Aktif', 'Karyawan aktif dan dapat dijadwalkan', true],
        ['INACTIVE', 'Nonaktif', 'Sementara tidak dijadwalkan', true],
        ['RESIGNED', 'Resign', 'Sudah tidak bekerja', true],
    ];

    public function run(): void
    {
        $this->seedList(EmploymentStatus::class, self::EMPLOYMENT_STATUSES);
        $this->seedList(EmploymentType::class, self::EMPLOYMENT_TYPES);
        $this->seedList(EmployeeStatus::class, self::EMPLOYEE_STATUSES);
    }

    /**
     * @param  class-string<ReferenceModel>  $model
     * @param  array<int, array{0: string, 1: string, 2: ?string, 3: bool}>  $rows
     */
    private function seedList(string $model, array $rows): void
    {
        foreach ($rows as $index => [$code, $name, $description, $isSystem]) {
            $row = $model::firstOrNew(['code' => $code]);

            if (! $row->exists) {
                $row->fill([
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => ($index + 1) * 10,
                    'status' => ReferenceModel::ACTIVE,
                ]);
            }

            // Reasserted on every run: the flag is a property of the code, not
            // an administrator's choice, and the delete guard reads it.
            $row->is_system = $isSystem;
            $row->save();
        }
    }
}
