<?php

namespace App\Exceptions;

class PayrollException extends BusinessRuleException
{
    /** Spec §44 / PAY-013. */
    public static function periodClosed(): self
    {
        return new self(
            'Periode payroll sudah ditutup. Buka kembali periode (ADMIN) sebelum menghitung ulang.',
            'PERIOD_CLOSED',
        );
    }

    public static function periodNotProcessed(): self
    {
        return new self(
            'Periode belum diproses. Jalankan Generate Payroll terlebih dahulu.',
            'PERIOD_NOT_PROCESSED',
        );
    }

    public static function periodNotClosed(): self
    {
        return new self('Periode ini belum ditutup.', 'PERIOD_NOT_CLOSED');
    }

    public static function overlappingPeriod(string $name): self
    {
        return new self(
            sprintf('Rentang tanggal bertabrakan dengan periode "%s".', $name),
            'OVERLAPPING_PERIOD',
        );
    }
}
