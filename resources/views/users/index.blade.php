@extends('layouts.app')

@section('title', 'User')
@section('page-subtitle', 'Akun login dan hak akses')

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah User
    </button>
@endsection

@section('content')
<x-data-table :columns="6">
    <x-slot:filters>
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari nama atau email…">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" id="roleFilter" name="role_id">
                <option value="">Semua Role</option>
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
        <th>Nama</th>
        <th>Email</th>
        <th>Role</th>
        <th>Karyawan</th>
        <th>Login Terakhir</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form>
    <div class="col-12 col-md-6">
        <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" placeholder="Budi Santoso" maxlength="150" required>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email"
               placeholder="budi@perusahaan.com" maxlength="150" required>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="password">Password <span class="text-danger js-password-required">*</span></label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 8 karakter"
               autocomplete="new-password" minlength="8">
        <div class="form-text js-password-hint d-none">Kosongkan bila tidak ingin mengubah password.</div>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
               placeholder="Ulangi password" autocomplete="new-password" minlength="8">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="role_id">Role <span class="text-danger">*</span></label>
        <select class="form-select" id="role_id" name="role_id" required></select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="employee_id">Karyawan</label>
        <select class="form-select" id="employee_id" name="employee_id"></select>
        {{-- AC-002: an EMPLOYEE account must resolve to an employee row. The
             text is swapped by users.js when no employee is free to link. --}}
        <div class="form-text js-employee-hint">Wajib diisi untuk akun dengan role EMPLOYEE.</div>
    </div>
    <div class="col-12 col-md-6">
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
    window.PARKOPS_URLS = {
        base: @json(route('users.index')),
        lookups: @json(route('lookups'))
    };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/users.js') }}"></script>
@endpush
