@extends('layouts.app')

@section('title', 'Assignment')
@section('page-subtitle', 'Penugasan karyawan ke lokasi dan shift')

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Assignment
    </button>
@endsection

@section('content')
<x-data-table :columns="7">
    <x-slot:filters>
        <div class="col-12 col-md-4">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari kode atau nama karyawan…">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" id="locationFilter" name="location_id">
                <option value="">Semua Lokasi</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" id="shiftFilter" name="shift_id">
                <option value="">Semua Shift</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" id="statusFilter" name="status">
                <option value="">Semua Status</option>
                <option value="ACTIVE">ACTIVE</option>
                <option value="INACTIVE">INACTIVE</option>
            </select>
        </div>
    </x-slot:filters>

    <x-slot:head>
        <th>Karyawan</th>
        <th>Lokasi</th>
        <th>Shift</th>
        <th class="text-center">Mulai</th>
        <th class="text-center">Selesai</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form>
    <div class="col-12">
        <label class="form-label" for="employee_id">Karyawan <span class="text-danger">*</span></label>
        <select class="form-select" id="employee_id" name="employee_id" required></select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="location_id">Lokasi <span class="text-danger">*</span></label>
        <select class="form-select" id="location_id" name="location_id" required></select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="shift_id">Shift <span class="text-danger">*</span></label>
        <select class="form-select" id="shift_id" name="shift_id" required></select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="start_date">Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="start_date" name="start_date" required>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="end_date">Tanggal Selesai</label>
        <input type="date" class="form-control" id="end_date" name="end_date">
        <div class="form-text">Kosongkan bila penugasan berlaku seterusnya.</div>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="ACTIVE">ACTIVE</option>
            <option value="INACTIVE">INACTIVE</option>
        </select>
    </div>
</x-modal-form>
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = {
        base: @json(route('assignments.index')),
        lookups: @json(route('lookups'))
    };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/assignments.js') }}"></script>
@endpush
