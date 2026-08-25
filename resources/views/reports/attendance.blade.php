@extends('layouts.app')

@section('title', 'Laporan Absensi')
@section('page-subtitle', 'Rekap kehadiran per karyawan')

@section('page-actions')
    <button class="btn btn-outline-success" id="exportButton">
        <i class="bi bi-filetype-csv me-1"></i>Export CSV
    </button>
@endsection

@section('content')
<div class="card overflow-hidden">
    <div class="card-body border-bottom py-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1" for="startDate">Dari Tanggal</label>
                <input type="date" class="form-control" id="startDate" value="{{ now()->startOfMonth()->toDateString() }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1" for="endDate">Sampai Tanggal</label>
                <input type="date" class="form-control" id="endDate" value="{{ now()->endOfMonth()->toDateString() }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="locationFilter">Lokasi</label>
                <select class="form-select" id="locationFilter"><option value="">Semua Lokasi</option></select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="searchInput">Cari Karyawan</label>
                <input type="search" class="form-control" id="searchInput" placeholder="Kode atau nama…">
            </div>
            <div class="col-6 col-md-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="includeInactive">
                    <label class="form-check-label small" for="includeInactive">Termasuk karyawan non-aktif</label>
                </div>
            </div>
        </div>
    </div>

    <div>
        <table class="table table-hover align-middle mb-0" id="reportTable" style="width:100%">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Karyawan</th>
                    <th class="text-center">Hadir</th>
                    <th class="text-center">Terlambat</th>
                    <th class="text-center">Belum Lengkap</th>
                    <th class="text-center">Alpha</th>
                    <th class="text-center">Hari Kerja</th>
                    <th class="text-end">Total Terlambat</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot class="table-light fw-semibold d-none" id="reportFoot">
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td class="text-center text-tabular" data-total="present">0</td>
                    <td class="text-center text-tabular" data-total="late">0</td>
                    <td class="text-center text-tabular" data-total="incomplete">0</td>
                    <td class="text-center text-tabular" data-total="absent">0</td>
                    <td class="text-center text-tabular" data-total="working_days">0</td>
                    <td class="text-end text-tabular" data-total="late_minutes">0</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<p class="small text-body-secondary mt-3 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Hari kerja dihitung dari absensi berstatus <strong>PRESENT</strong> dan <strong>LATE</strong> saja (spec §39).
    Absensi yang belum di-check-out berstatus <strong>INCOMPLETE</strong> dan tidak terhitung.
</p>
@endsection

@push('scripts')
<script>
    window.PARKOPS_URLS = {
        base: @json(route('reports.attendance')),
        export: @json(route('reports.attendance.export')),
        lookups: @json(route('lookups'))
    };
</script>
<script src="{{ asset('js/reports.js') }}"></script>
<script>
    jQuery(function () { ParkOps.attendanceReport(); });
</script>
@endpush
