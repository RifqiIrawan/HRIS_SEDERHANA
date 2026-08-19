<?php

namespace App\Exceptions;

class AssignmentException extends BusinessRuleException
{
    public static function inactiveLocation(): self
    {
        return new self('Lokasi yang dipilih tidak aktif.', 'LOCATION_INACTIVE');
    }

    /**
     * A rotation needs something to rotate between: with a single active shift
     * every cycle would land on the same one.
     */
    public static function notEnoughShifts(): self
    {
        return new self(
            'Rotasi membutuhkan minimal 2 shift aktif. Aktifkan shift lain di menu Shift terlebih dahulu.',
            'NOT_ENOUGH_SHIFTS',
        );
    }

    /**
     * The same shortage, but reached by unticking shifts rather than by the
     * master data — so the fix is in the dialog, not in the Shift menu.
     */
    public static function notEnoughSelectedShifts(): self
    {
        return new self(
            'Pilih minimal 2 shift untuk dirotasi.',
            'NOT_ENOUGH_SELECTED_SHIFTS',
        );
    }

    public static function noEmployees(): self
    {
        return new self('Tidak ada karyawan aktif yang cocok dengan pilihan.', 'NO_EMPLOYEES');
    }
}
