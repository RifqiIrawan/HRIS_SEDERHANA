@extends('layouts.app')

@section('title', 'Karyawan')
@section('page-subtitle', 'Master data karyawan / juru parkir')

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Karyawan
    </button>
@endsection

@section('content')
<x-data-table :columns="8">
    <x-slot:filters>
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari kode, nama, NIK, atau no. HP…">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" id="statusFilter" name="status">
                <option value="">Semua Status</option>
                <option value="ACTIVE">ACTIVE</option>
                <option value="INACTIVE">INACTIVE</option>
                <option value="RESIGNED">RESIGNED</option>
            </select>
        </div>
    </x-slot:filters>

    <x-slot:head>
        <th>Kode</th>
        <th>Nama</th>
        <th>NIK</th>
        <th>No. HP</th>
        <th>Tipe</th>
        <th>Bergabung</th>
        <th class="text-end">Upah Harian</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form>
    <div class="col-12 col-md-6">
        <label class="form-label" for="employee_code">Kode Karyawan <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="employee_code" name="employee_code" maxlength="30" required>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="nik">NIK</label>
        <input type="text" class="form-control" id="nik" name="nik" maxlength="30">
    </div>
    <div class="col-12">
        <label class="form-label" for="full_name">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="full_name" name="full_name" maxlength="150" required>
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label" for="gender">Jenis Kelamin</label>
        <select class="form-select" id="gender" name="gender">
            <option value="">—</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
        </select>
    </div>
    <div class="col-6 col-md-4">
        <label class="form-label" for="birth_place">Tempat Lahir</label>
        <input type="text" class="form-control" id="birth_place" name="birth_place" maxlength="100">
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label" for="birth_date">Tanggal Lahir</label>
        <input type="date" class="form-control" id="birth_date" name="birth_date">
    </div>
    <div class="col-12 col-md-5">
        <label class="form-label" for="phone">No. HP</label>
        <input type="text" class="form-control" id="phone" name="phone" maxlength="30">
    </div>
    <div class="col-12">
        <label class="form-label" for="address">Alamat</label>
        <textarea class="form-control" id="address" name="address" rows="2" maxlength="500"></textarea>
    </div>

    <div class="col-12"><hr class="my-1"></div>

    <div class="col-12 col-md-4">
        <label class="form-label" for="employment_status">Status Kepegawaian <span class="text-danger">*</span></label>
        <select class="form-select" id="employment_status" name="employment_status" required>
            <option value="PERCOBAAN">PERCOBAAN</option>
            <option value="KONTRAK">KONTRAK</option>
            <option value="TETAP">TETAP</option>
        </select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="employment_type">Tipe <span class="text-danger">*</span></label>
        {{-- Spec §11: the MVP only pays daily-rate staff. --}}
        <select class="form-select" id="employment_type" name="employment_type" required>
            <option value="DAILY">DAILY (Harian)</option>
        </select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="join_date">Tanggal Bergabung</label>
        <input type="date" class="form-control" id="join_date" name="join_date">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="daily_rate">Upah Harian (Rp) <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="daily_rate" name="daily_rate" min="0" step="1000" required>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="ACTIVE">ACTIVE</option>
            <option value="INACTIVE">INACTIVE</option>
            <option value="RESIGNED">RESIGNED</option>
        </select>
    </div>
</x-modal-form>
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = { base: @json(route('employees.index')) };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/employees.js') }}"></script>
@endpush
