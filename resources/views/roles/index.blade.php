@extends('layouts.app')

@section('title', 'Role')
@section('page-subtitle', 'Definisi peran akses sistem')

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Role
    </button>
@endsection

@section('content')
<div class="alert alert-light border small">
    <i class="bi bi-shield-lock me-1"></i>
    Role <strong>ADMIN</strong>, <strong>HR</strong>, dan <strong>EMPLOYEE</strong> adalah role bawaan sistem —
    kode dan penghapusannya dikunci karena logika otorisasi merujuk ke kode tersebut.
</div>

<x-data-table :columns="5">
    <x-slot:filters>
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari kode atau nama role…">
        </div>
    </x-slot:filters>

    <x-slot:head>
        <th>Kode</th>
        <th>Nama Role</th>
        <th class="text-center">Jumlah User</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form size="modal-md">
    <div class="col-12">
        <label class="form-label" for="role_code">Kode Role <span class="text-danger">*</span></label>
        <input type="text" class="form-control text-uppercase" id="role_code" name="role_code" maxlength="30" required>
        <div class="form-text">Huruf kapital, angka, dan garis bawah saja.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="role_name">Nama Role <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="role_name" name="role_name" maxlength="100" required>
    </div>
    <div class="col-12">
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
    window.HRIS_URLS = { base: @json(route('roles.index')) };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/roles.js') }}"></script>
@endpush
