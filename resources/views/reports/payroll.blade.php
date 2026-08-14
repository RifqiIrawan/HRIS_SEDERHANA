@extends('layouts.app')

@section('title', 'Laporan Payroll')
@section('page-subtitle', 'Rekap penggajian per periode')

@section('page-actions')
    <button class="btn btn-outline-success" id="exportButton">
        <i class="bi bi-filetype-csv me-1"></i>Export CSV
    </button>
@endsection

@section('content')
<div class="card overflow-hidden">
    <div class="card-body border-bottom py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1" for="periodSelect">Periode Payroll</label>
                <select class="form-select" id="periodSelect"></select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1" for="searchInput">Cari Karyawan</label>
                <input type="search" class="form-control" id="searchInput" placeholder="Kode atau nama…">
            </div>
            <div class="col-12 col-md-5">
                <div class="small text-body-secondary" id="periodInfo"></div>
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
                    <th class="text-center">Hari Kerja</th>
                    <th class="text-end">Upah Harian</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Potongan</th>
                    <th class="text-end">Net</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot class="table-light fw-semibold d-none" id="reportFoot">
                <tr>
                    <td colspan="4">TOTAL</td>
                    <td class="text-center text-tabular" data-total="working_days">0</td>
                    <td></td>
                    <td class="text-end text-tabular" data-total="gross">0</td>
                    <td class="text-end text-tabular" data-total="deduction">0</td>
                    <td class="text-end text-tabular" data-total="net">0</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = {
        base: @json(route('reports.payroll')),
        export: @json(route('reports.payroll.export')),
        lookups: @json(route('lookups'))
    };
</script>
<script src="{{ asset('js/reports.js') }}"></script>
<script>
    jQuery(function () { HRIS.payrollReport(); });
</script>
@endpush
