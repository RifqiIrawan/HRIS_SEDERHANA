@extends('layouts.app')

@section('title', 'Proses & Daftar Payroll')
@section('page-subtitle', 'Hari kerja × upah harian − potongan = upah bersih')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1" for="periodSelect">Periode Payroll</label>
                <select class="form-select" id="periodSelect"></select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1" for="searchInput">Cari Karyawan</label>
                <input type="search" class="form-control" id="searchInput" placeholder="Kode atau nama…">
            </div>
            <div class="col-12 col-md-5 d-flex flex-wrap gap-2 justify-content-md-end">
                <button class="btn btn-primary" id="generateButton" disabled>
                    <i class="bi bi-arrow-repeat me-1"></i>Generate Payroll
                </button>
                <button class="btn btn-outline-dark" id="closeButton" disabled>
                    <i class="bi bi-lock me-1"></i>Tutup Periode
                </button>
                @if (auth()->user()->isAdmin())
                    <button class="btn btn-outline-secondary d-none" id="reopenButton">
                        <i class="bi bi-unlock me-1"></i>Buka Kembali
                    </button>
                @endif
            </div>
        </div>

        <div class="alert alert-light border small mt-3 mb-0 d-none" id="periodInfo"></div>
    </div>
</div>

{{-- Spec §43 — totals across the period. --}}
<div class="row g-3 mb-3">
    @foreach ([
        ['label' => 'Karyawan', 'key' => 'employees', 'money' => false, 'tone' => 'primary', 'icon' => 'people-fill'],
        ['label' => 'Total Hari Kerja', 'key' => 'working_days', 'money' => false, 'tone' => 'info', 'icon' => 'calendar-check-fill'],
        ['label' => 'Total Gross', 'key' => 'gross', 'money' => true, 'tone' => 'success', 'icon' => 'cash'],
        ['label' => 'Total Net', 'key' => 'net', 'money' => true, 'tone' => 'dark', 'icon' => 'wallet2'],
    ] as $tile)
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="stat-icon text-bg-{{ $tile['tone'] }} bg-opacity-10 text-{{ $tile['tone'] }}">
                        <i class="bi bi-{{ $tile['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="{{ $tile['money'] ? 'h5 mb-0 text-tabular' : 'stat-value' }}"
                             data-total="{{ $tile['key'] }}" data-money="{{ $tile['money'] ? 1 : 0 }}">—</div>
                        <div class="stat-label">{{ $tile['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card overflow-hidden">
    <table class="table table-hover align-middle mb-0" id="payrollTable" style="width:100%">
        <thead>
            <tr>
                <th>Karyawan</th>
                <th class="text-center">Hadir</th>
                <th class="text-center">Terlambat</th>
                <th class="text-center">Hari Kerja</th>
                <th class="text-end">Upah Harian</th>
                <th class="text-end">Gross</th>
                <th class="text-end">Potongan</th>
                <th class="text-end">Net</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

{{-- ── Deduction modal (spec §41, §42) ────────────────────────────── --}}
<div class="modal fade" id="deductionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Potongan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary" id="deductionContext"></p>

                <div class="table-wrap mb-3">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end" style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody id="deductionList"></tbody>
                    </table>
                </div>

                <form id="deductionForm" class="row g-2 align-items-end" novalidate>
                    <div class="col-12 col-sm-7">
                        <label class="form-label small mb-1" for="deduction_description">Keterangan</label>
                        <input type="text" class="form-control" id="deduction_description" name="description"
                               maxlength="150" placeholder="Kasbon / Denda / Potongan Lain" required>
                    </div>
                    <div class="col-8 col-sm-3">
                        <label class="form-label small mb-1" for="deduction_amount">Jumlah (Rp)</label>
                        <input type="number" class="form-control" id="deduction_amount" name="amount"
                               min="1" step="1000" required>
                    </div>
                    <div class="col-4 col-sm-2 d-grid">
                        <button type="submit" class="btn btn-primary js-add-deduction">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = {
        base: @json(url('payroll')),
        lookups: @json(route('lookups')),
        isAdmin: @json(auth()->user()->isAdmin())
    };
    window.HRIS_SELECTED_PERIOD = @json(request()->integer('period_id') ?: null);
</script>
<script src="{{ asset('js/payroll.js') }}"></script>
@endpush
