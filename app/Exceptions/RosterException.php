<?php

namespace App\Exceptions;

class RosterException extends BusinessRuleException
{
    public static function unknownShiftCode(string $code): self
    {
        return new self(
            sprintf('Kode shift "%s" tidak ditemukan atau tidak aktif.', $code),
            'UNKNOWN_SHIFT_CODE',
        );
    }

    public static function emptyPattern(): self
    {
        return new self('Pola shift tidak boleh kosong.', 'EMPTY_PATTERN');
    }

    public static function inactiveLocation(): self
    {
        return new self('Lokasi yang dipilih tidak aktif.', 'LOCATION_INACTIVE');
    }
}
