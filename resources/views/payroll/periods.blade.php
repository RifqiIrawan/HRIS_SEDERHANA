@extends('layouts.app')

@section('title', 'Periode Payroll')
@section('page-subtitle', 'Rentang tanggal penggajian — OPEN → PROCESSED → CLOSED')

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Periode
    </button>
@endsection

@section('content')
<x-data-table :columns="7">
    <x-slot:filters>
        <div class="col-6 col-md-3">
            <select class="form-select" id="statusFilter" name="status">
                <option value="">Semua Status</option>
                <option value="OPEN">OPEN</option>
                <option value="PROCESSED">PROCESSED</option>
                <option value="CLOSED">CLOSED</option>
            </select>
        </div>
    </x-slot:filters>

    <x-slot:head>
        <th>Kode</th>
        <th>Nama Periode</th>
        <th class="text-center">Mulai</th>
        <th class="text-center">Selesai</th>
        <th class="text-center">Baris Payroll</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form>
    <div class="col-12 col-md-5">
        <label class="form-label" for="period_code">Kode Periode <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="period_code" name="period_code" maxlength="30"
               placeholder="2026-08" required>
    </div>
    <div class="col-12 col-md-7">
        <label class="form-label" for="period_name">Nama Periode <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="period_name" name="period_name" maxlength="100"
               placeholder="Agustus 2026" required>
    </div>
    <div class="col-6">
        <label class="form-label" for="start_date">Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="start_date" name="start_date" required>
    </div>
    <div class="col-6">
        <label class="form-label" for="end_date">Tanggal Selesai <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="end_date" name="end_date" required>
    </div>
    <div class="col-12">
        <div class="alert alert-light border small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Rentang periode tidak boleh bertumpang tindih dengan periode lain, agar satu hari absensi
            tidak terhitung dua kali.
        </div>
    </div>
</x-modal-form>
@endsection

@push('scripts')
<script>
    window.PARKOPS_URLS = {
        base: @json(route('payroll.periods')),
        store: @json(route('payroll.periods.store')),
        payroll: @json(route('payroll.index'))
    };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/payroll-periods.js') }}"></script>
@endpush
