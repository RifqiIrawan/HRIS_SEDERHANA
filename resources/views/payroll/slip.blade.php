{{--
    Printable payslip (spec §43).

    Standalone rather than @extends('layouts.app'): a payslip is paper, so it
    carries no sidebar, no navbar and no scripts beyond the print trigger. The
    browser's print dialog produces the PDF, which is why no PDF library is
    needed anywhere in this feature.
--}}
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Gaji {{ $employee->employee_code }} · {{ $period->period_name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { font-family: Inter, system-ui, sans-serif; background: #f1f3f5; color: #212529; }

        .slip { max-width: 780px; margin: 1.5rem auto; background: #fff; padding: 2.25rem; }
        .slip-title { letter-spacing: .12em; text-transform: uppercase; font-size: .75rem; }
        .slip-table th, .slip-table td { padding: .45rem .25rem; vertical-align: top; }
        .num { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .meta td:first-child { width: 42%; color: #6c757d; }
        .rule { border-top: 1px solid #dee2e6; }
        .rule-strong { border-top: 2px solid #212529; }

        /* Paper: drop the screen chrome and let the sheet fill the page. */
        @media print {
            @page { size: A4; margin: 14mm; }
            body { background: #fff; }
            .slip { max-width: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print text-center pt-3">
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Cetak / Simpan PDF
    </button>
    <button type="button" class="btn btn-light btn-sm" onclick="window.close()">Tutup</button>
</div>

<div class="slip shadow-sm">

    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
        <div>
            <div class="fw-bold fs-5">{{ config('app.name') }}</div>
            <div class="slip-title text-secondary mt-1">Slip Gaji Karyawan</div>
        </div>
        <div class="text-end small text-secondary">
            <div>{{ $period->period_name }}</div>
            <div>{{ $period->start_date->format('d M Y') }} — {{ $period->end_date->format('d M Y') }}</div>
            <div class="mt-1">
                <span class="badge {{ $period->status === 'CLOSED' ? 'text-bg-dark' : 'text-bg-warning' }}">
                    {{ $period->status }}
                </span>
            </div>
        </div>
    </div>

    {{-- An open period is still recalculable, so the sheet must not read as final. --}}
    @if ($period->status !== 'CLOSED')
        <div class="alert alert-warning py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Periode belum ditutup — angka pada slip ini masih dapat berubah bila payroll digenerate ulang.
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-6">
            <div class="slip-title text-secondary mb-2">Karyawan</div>
            <table class="table table-sm table-borderless slip-table meta mb-0 small">
                <tr><td>Kode</td><td class="fw-semibold">{{ $employee->employee_code }}</td></tr>
                <tr><td>Nama</td><td class="fw-semibold">{{ $employee->full_name }}</td></tr>
                <tr><td>Status</td><td>{{ $employee->employment_status ?? '−' }}</td></tr>
                <tr><td>Tipe</td><td>{{ $employee->employment_type ?? '−' }}</td></tr>
            </table>
        </div>
        <div class="col-6">
            <div class="slip-title text-secondary mb-2">Kehadiran</div>
            <table class="table table-sm table-borderless slip-table meta mb-0 small">
                <tr><td>Hadir</td><td class="fw-semibold">{{ $payroll->present_days }} hari</td></tr>
                <tr><td>Terlambat</td><td>{{ $payroll->late_days }} hari</td></tr>
                <tr><td>Hari dibayar</td><td class="fw-semibold">{{ $payroll->working_days }} hari</td></tr>
                <tr><td>Upah harian</td><td>Rp {{ number_format($payroll->daily_rate, 0, ',', '.') }}</td></tr>
            </table>
        </div>
    </div>

    <div class="slip-title text-secondary mb-2">Rincian</div>
    <table class="table table-sm slip-table mb-0">
        <tbody>
            <tr>
                <td>Upah kotor
                    <span class="text-secondary small">
                        ({{ $payroll->working_days }} hari × Rp {{ number_format($payroll->daily_rate, 0, ',', '.') }})
                    </span>
                </td>
                <td class="num fw-semibold">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</td>
            </tr>

            @forelse ($deductions as $deduction)
                <tr>
                    <td class="ps-3 text-secondary">− {{ $deduction->description }}</td>
                    <td class="num text-danger">− Rp {{ number_format($deduction->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="ps-3 text-secondary fst-italic" colspan="2">Tidak ada potongan.</td>
                </tr>
            @endforelse

            <tr class="rule">
                <td class="text-secondary">Total potongan</td>
                <td class="num text-danger">− Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
            </tr>

            <tr class="rule-strong">
                <td class="fw-bold pt-3">Upah bersih diterima</td>
                <td class="num fw-bold fs-5 pt-3">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="row mt-5 pt-3 small text-center">
        <div class="col-6">
            <div class="text-secondary">Diterima oleh</div>
            <div style="height:70px"></div>
            <div class="border-top d-inline-block px-4 pt-1">{{ $employee->full_name }}</div>
        </div>
        <div class="col-6">
            <div class="text-secondary">Dibuat oleh</div>
            <div style="height:70px"></div>
            <div class="border-top d-inline-block px-4 pt-1">{{ auth()->user()->name }}</div>
        </div>
    </div>

    <div class="text-center text-secondary mt-4" style="font-size:.7rem">
        Dicetak {{ now()->translatedFormat('d F Y H:i') }} — dokumen ini dihasilkan oleh sistem.
    </div>
</div>

</body>
</html>
