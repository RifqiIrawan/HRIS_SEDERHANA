@extends('layouts.app')

@section('title', 'Shift')
@section('page-subtitle', 'Pola jam kerja — shift malam ditandai lintas hari')

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Shift
    </button>
@endsection

@section('content')
<x-data-table :columns="7">
    <x-slot:filters>
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari kode atau nama shift…">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" id="statusFilter" name="status">
                <option value="">Semua Status</option>
                <option value="ACTIVE">ACTIVE</option>
                <option value="INACTIVE">INACTIVE</option>
            </select>
        </div>
    </x-slot:filters>

    <x-slot:head>
        <th>Kode</th>
        <th>Nama Shift</th>
        <th class="text-center">Mulai</th>
        <th class="text-center">Selesai</th>
        <th class="text-center">Lintas Hari</th>
        <th class="text-center">Toleransi</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form>
    <div class="col-12 col-md-4">
        <label class="form-label" for="shift_code">Kode Shift <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="shift_code" name="shift_code" maxlength="30" required>
    </div>
    <div class="col-12 col-md-8">
        <label class="form-label" for="shift_name">Nama Shift <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="shift_name" name="shift_name" maxlength="100" required>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label" for="start_time">Jam Mulai <span class="text-danger">*</span></label>
        <input type="time" class="form-control" id="start_time" name="start_time" required>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label" for="end_time">Jam Selesai <span class="text-danger">*</span></label>
        <input type="time" class="form-control" id="end_time" name="end_time" required>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label" for="late_tolerance_minutes">Toleransi (menit) <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="late_tolerance_minutes" name="late_tolerance_minutes"
               min="0" max="240" required>
    </div>
    <div class="col-12 col-md-3">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="ACTIVE">ACTIVE</option>
            <option value="INACTIVE">INACTIVE</option>
        </select>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="cross_day" name="cross_day" value="1">
            <label class="form-check-label" for="cross_day">
                Lintas Hari (shift malam)
            </label>
            {{-- Spec §17 — this is what makes shift 3 end at 06:00 the next day. --}}
            <div class="form-text">
                Centang bila shift berakhir di hari berikutnya, misalnya 22:00 → 06:00.
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="alert alert-light border small mb-0" id="shiftPreview"></div>
    </div>
</x-modal-form>
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = { base: @json(route('shifts.index')) };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/shifts.js') }}"></script>
@endpush
