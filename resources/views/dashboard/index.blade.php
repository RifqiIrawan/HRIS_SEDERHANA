@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-subtitle', 'Ringkasan operasional ' . now()->translatedFormat('d F Y'))

@section('page-actions')
    <button class="btn btn-outline-secondary btn-sm" id="refreshDashboard">
        <i class="bi bi-arrow-clockwise me-1"></i>Muat Ulang
    </button>
@endsection

@section('content')
@if ($scope === 'ORGANISATION')

    {{-- Spec §8 — four counters. --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['label' => 'Total Karyawan', 'key' => 'total_employee', 'icon' => 'people-fill', 'tone' => 'primary'],
            ['label' => 'Hadir Hari Ini', 'key' => 'hadir', 'icon' => 'check-circle-fill', 'tone' => 'success'],
            ['label' => 'Terlambat', 'key' => 'terlambat', 'icon' => 'clock-fill', 'tone' => 'warning'],
            ['label' => 'Belum Absen', 'key' => 'belum_absen', 'icon' => 'exclamation-circle-fill', 'tone' => 'danger'],
        ] as $tile)
            <div class="col-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="stat-icon text-bg-{{ $tile['tone'] }} bg-opacity-10 text-{{ $tile['tone'] }}">
                            <i class="bi bi-{{ $tile['icon'] }}"></i>
                        </span>
                        <div>
                            <div class="stat-value" data-stat="{{ $tile['key'] }}">{{ $stats[$tile['key']] }}</div>
                            <div class="stat-label">{{ $tile['label'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header bg-body border-bottom fw-semibold">
                    <i class="bi bi-cash-stack me-1"></i>Payroll Aktif
                </div>
                <div class="card-body">
                    @if ($stats['payroll'])
                        <h6 class="mb-1">{{ $stats['payroll']['name'] }}</h6>
                        <p class="text-body-secondary small mb-3">
                            {{ $stats['payroll']['range'] }}
                            <span class="badge text-bg-info ms-1">{{ $stats['payroll']['status'] }}</span>
                        </p>

                        <dl class="row mb-0 small">
                            <dt class="col-7 fw-normal text-body-secondary">Total Karyawan</dt>
                            <dd class="col-5 text-end text-tabular mb-2">{{ number_format($stats['payroll']['employees'], 0, ',', '.') }}</dd>

                            <dt class="col-7 fw-normal text-body-secondary">Working Days</dt>
                            <dd class="col-5 text-end text-tabular mb-2">{{ number_format($stats['payroll']['working_days'], 0, ',', '.') }}</dd>

                            <dt class="col-7 fw-normal text-body-secondary">Total Gross</dt>
                            <dd class="col-5 text-end text-tabular mb-2">Rp {{ number_format((float) $stats['payroll']['gross'], 0, ',', '.') }}</dd>

                            <dt class="col-7 fw-normal text-body-secondary">Total Potongan</dt>
                            <dd class="col-5 text-end text-tabular mb-2 text-danger">− Rp {{ number_format((float) $stats['payroll']['deduction'], 0, ',', '.') }}</dd>
                        </dl>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between align-items-baseline">
                            <span class="fw-semibold">Total Net</span>
                            <span class="h5 mb-0 text-tabular">Rp {{ number_format((float) $stats['payroll']['net'], 0, ',', '.') }}</span>
                        </div>

                        <a href="{{ route('payroll.index') }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                            Buka Payroll
                        </a>
                    @else
                        <div class="text-center text-body-secondary py-4">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:2rem"></i>
                            <p class="small mb-3">Belum ada periode payroll aktif.</p>
                            <a href="{{ route('payroll.periods') }}" class="btn btn-sm btn-primary">Buat Periode</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-semibold"><i class="bi bi-activity me-1"></i>Absensi Terbaru Hari Ini</span>
                    <a href="{{ route('attendance.monitoring') }}" class="small text-decoration-none">Lihat semua</a>
                </div>
                <div class="table-wrap">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Lokasi</th>
                                <th class="text-center">Masuk</th>
                                <th class="text-center">Keluar</th>
                                <th class="text-end">Jarak</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stats['recent'] as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row['employee'] }}</div>
                                        <div class="small text-body-secondary">{{ $row['code'] }}</div>
                                    </td>
                                    <td class="small">{{ $row['location'] }}</td>
                                    <td class="text-center text-tabular">{{ $row['check_in_at'] ?? '−' }}</td>
                                    <td class="text-center text-tabular">{{ $row['check_out_at'] ?? '−' }}</td>
                                    <td class="text-end text-tabular">{{ $row['distance'] !== null ? number_format($row['distance'], 1) . ' m' : '−' }}</td>
                                    <td>
                                        <span class="badge badge-status text-bg-{{ ['PRESENT' => 'success', 'LATE' => 'warning', 'INCOMPLETE' => 'secondary', 'ABSENT' => 'danger'][$row['status']] ?? 'secondary' }}">
                                            {{ $row['status'] }}
                                        </span>
                                        @if ($row['late_minutes'] > 0)
                                            <div class="small text-warning-emphasis">+{{ $row['late_minutes'] }} mnt</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="table-empty"><td colspan="6">Belum ada absensi hari ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@else

    {{-- EMPLOYEE view — their own month, and today's shift. --}}
    @if (! ($stats['linked'] ?? false))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Akun Anda belum terhubung ke data karyawan. Hubungi HR agar absensi dapat digunakan.
        </div>
    @else
        <div class="row g-3 mb-3">
            @foreach ([
                ['label' => 'Hadir Bulan Ini', 'key' => 'present_this_month', 'icon' => 'check-circle-fill', 'tone' => 'success'],
                ['label' => 'Terlambat', 'key' => 'late_this_month', 'icon' => 'clock-fill', 'tone' => 'warning'],
                ['label' => 'Hari Kerja', 'key' => 'working_days_this_month', 'icon' => 'calendar-check-fill', 'tone' => 'primary'],
                ['label' => 'Belum Lengkap', 'key' => 'incomplete_this_month', 'icon' => 'hourglass-split', 'tone' => 'secondary'],
            ] as $tile)
                <div class="col-6 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="stat-icon text-bg-{{ $tile['tone'] }} bg-opacity-10 text-{{ $tile['tone'] }}">
                                <i class="bi bi-{{ $tile['icon'] }}"></i>
                            </span>
                            <div>
                                <div class="stat-value">{{ $stats[$tile['key']] }}</div>
                                <div class="stat-label">{{ $tile['label'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card h-100">
                    <div class="card-header bg-body border-bottom fw-semibold">
                        <i class="bi bi-calendar-event me-1"></i>Jadwal Hari Ini
                    </div>
                    <div class="card-body">
                        @if ($stats['today_roster'])
                            <div class="d-flex flex-wrap gap-4">
                                <div>
                                    <div class="stat-label">Shift</div>
                                    <div class="fs-5 fw-semibold">{{ $stats['today_roster']['shift'] }}</div>
                                </div>
                                <div>
                                    <div class="stat-label">Lokasi</div>
                                    <div class="fs-5 fw-semibold">{{ $stats['today_roster']['location'] }}</div>
                                </div>
                                <div>
                                    <div class="stat-label">Status</div>
                                    <div class="fs-5">{!! '<span class="badge text-bg-primary">' . e($stats['today_roster']['status']) . '</span>' !!}</div>
                                </div>
                            </div>

                            @if ($stats['today_roster']['start'])
                                <p class="text-body-secondary small mt-3 mb-0">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $stats['today_roster']['start'] }} — {{ $stats['today_roster']['end'] }}
                                </p>
                            @endif

                            <a href="{{ route('attendance.index') }}" class="btn btn-primary mt-3">
                                <i class="bi bi-geo-alt me-1"></i>Buka Halaman Absensi
                            </a>
                        @else
                            <div class="text-center text-body-secondary py-4">
                                <i class="bi bi-calendar-x d-block mb-2" style="font-size:2rem"></i>
                                <p class="small mb-0">Tidak ada jadwal untuk hari ini.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card h-100">
                    <div class="card-header bg-body border-bottom fw-semibold">
                        <i class="bi bi-wallet2 me-1"></i>Estimasi Upah Bulan Ini
                    </div>
                    <div class="card-body">
                        <div class="h3 text-tabular mb-1">
                            Rp {{ number_format($stats['estimated_earning'], 0, ',', '.') }}
                        </div>
                        <p class="small text-body-secondary mb-0">
                            {{ $stats['working_days_this_month'] }} hari kerja ×
                            Rp {{ number_format($stats['employee']['daily_rate'], 0, ',', '.') }}
                        </p>
                        <hr>
                        <p class="small text-body-secondary mb-0">
                            Estimasi, belum termasuk potongan. Angka final mengikuti perhitungan payroll HR.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endif
@endsection

@push('scripts')
<script>
    jQuery(function ($) {
        $('#refreshDashboard').on('click', function () {
            window.location.reload();
        });
    });
</script>
@endpush
