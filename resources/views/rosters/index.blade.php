@extends('layouts.app')

@section('title', 'Shift Roster')
@section('page-subtitle', 'Jadwal aktual per karyawan per tanggal')

@section('page-actions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateModal">
        <i class="bi bi-magic me-1"></i>Generate Roster
    </button>
@endsection

@section('content')
<div class="card">
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
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1" for="locationFilter">Lokasi</label>
                <select class="form-select" id="locationFilter">
                    <option value="">Semua Lokasi</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1" for="searchInput">Cari Karyawan</label>
                <input type="search" class="form-control" id="searchInput" placeholder="Kode atau nama…">
            </div>
        </div>
    </div>

    {{-- The header row is rebuilt whenever the date range changes; DataTables
         fills the body and renders the pager below it. --}}
    <table class="table table-bordered table-sm mb-0" id="rosterTable" style="width:100%">
        <thead class="table-light">
            <tr id="rosterHeader">
                <th style="min-width:190px">Karyawan</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="mt-3 small text-body-secondary d-flex flex-wrap gap-3">
    <span><span class="badge text-bg-primary">S1</span> shift terjadwal</span>
    <span><span class="badge text-bg-secondary">OFF</span> hari libur</span>
    <span><span class="badge text-bg-success">S3</span> sudah ada absensi</span>
    <span><i class="bi bi-dash text-body-secondary"></i> belum dijadwalkan</span>
</div>

{{-- ── Generate modal (spec §16) ──────────────────────────────────── --}}
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="generateForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Generate Shift Roster</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="gen_location_id">Lokasi <span class="text-danger">*</span></label>
                            <select class="form-select" id="gen_location_id" name="location_id" required></select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="gen_pattern">Pola Shift <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="gen_pattern" name="pattern"
                                   value="S1,S2,S3,OFF" required>
                            <div class="form-text">
                                Kode shift dipisah koma, gunakan <code>OFF</code> untuk hari libur.
                                Contoh: <code>S1,S2,S3,OFF</code> atau <code>S1,S1,S2,S2,S3,S3,OFF</code>.
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="gen_start_date">Dari <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="gen_start_date" name="start_date" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="gen_end_date">Sampai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="gen_end_date" name="end_date" required>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="gen_overwrite" name="overwrite" value="1">
                                <label class="form-check-label" for="gen_overwrite">Timpa jadwal yang ada</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Karyawan <span class="text-danger">*</span></span>
                                <span>
                                    <button type="button" class="btn btn-sm btn-link p-0 me-2" id="selectAllEmployees">Pilih semua</button>
                                    <button type="button" class="btn btn-sm btn-link p-0" id="clearEmployees">Kosongkan</button>
                                </span>
                            </label>
                            <input type="search" class="form-control form-control-sm mb-2" id="employeeSearch"
                                   placeholder="Saring daftar karyawan…">
                            <div class="border rounded p-2" id="employeeList" style="max-height:220px; overflow-y:auto">
                                <div class="text-body-secondary small p-2">Memuat karyawan…</div>
                            </div>
                            <div class="form-text"><span id="selectedCount">0</span> karyawan dipilih.</div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-light border small mb-0" id="patternPreview">
                                Isi pola dan rentang tanggal untuk melihat pratinjau.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary js-generate">
                        <i class="bi bi-magic me-1"></i>Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Single-cell edit modal ─────────────────────────────────────── --}}
<div class="modal fade" id="cellModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="cellForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-body-secondary" id="cellContext"></p>
                    <div class="mb-3">
                        <label class="form-label" for="cell_location_id">Lokasi <span class="text-danger">*</span></label>
                        <select class="form-select" id="cell_location_id" name="location_id" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cell_status">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="cell_status" name="status" required>
                            <option value="SCHEDULED">SCHEDULED</option>
                            <option value="OFF">OFF</option>
                            <option value="CANCELLED">CANCELLED</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="cell_shift_id">Shift</label>
                        <select class="form-select" id="cell_shift_id" name="shift_id"></select>
                        <div class="form-text">Wajib diisi bila status SCHEDULED.</div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="deleteCell">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </button>
                    <div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary js-save-cell">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = {
        base: @json(route('rosters.index')),
        generate: @json(route('rosters.generate')),
        preview: @json(route('rosters.preview')),
        lookups: @json(route('lookups'))
    };
</script>
<script src="{{ asset('js/rosters.js') }}"></script>
@endpush
