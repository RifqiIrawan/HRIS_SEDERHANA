<?php

namespace App\Exceptions;

/**
 * A refusal from the attendance flow — no roster, GPS too coarse, outside the
 * geofence, duplicate check-in, and so on. Expected outcomes, not faults.
 */
class AttendanceException extends BusinessRuleException
{
    public static function noRoster(): self
    {
        return new self(
            'Tidak ada jadwal shift aktif untuk Anda saat ini.',
            'NO_ACTIVE_ROSTER',
        );
    }

    public static function duplicateCheckIn(): self
    {
        return new self(
            'Anda sudah melakukan check-in untuk shift ini.',
            'DUPLICATE_CHECK_IN',
        );
    }

    public static function noOpenAttendance(): self
    {
        return new self(
            'Tidak ada check-in aktif. Lakukan check-in terlebih dahulu.',
            'NO_OPEN_ATTENDANCE',
        );
    }

    public static function checkOutWindowExpired(): self
    {
        return new self(
            'Batas waktu check-out shift ini sudah lewat. Hubungi HR untuk koreksi absensi.',
            'CHECK_OUT_WINDOW_EXPIRED',
        );
    }

    public static function employeeInactive(): self
    {
        return new self(
            'Data karyawan Anda tidak aktif. Hubungi HR.',
            'EMPLOYEE_INACTIVE',
        );
    }

    public static function notAnEmployee(): self
    {
        return new self(
            'Akun Anda belum terhubung ke data karyawan.',
            'NOT_LINKED_TO_EMPLOYEE',
            statusCode: 403,
        );
    }
}
