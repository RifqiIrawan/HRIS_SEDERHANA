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

<div class="card mt-4">
    <div class="card-header bg-body border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <span class="fw-semibold"><i class="bi bi-diagram-3 me-1"></i>Akses Menu</span>
            <div class="small text-body-secondary mt-1">
                Centang menu yang boleh dibuka tiap role. Pengaturan ini juga menutup URL-nya,
                bukan sekadar menyembunyikan menu.
            </div>
        </div>
        <button class="btn btn-primary" id="saveMenuAccess">
            <i class="bi bi-check2 me-1"></i>Simpan Akses
        </button>
    </div>

    <div class="table-wrap">
        <table class="table table-hover align-middle mb-0" id="menuAccessTable">
            <thead>
                <tr>
                    <th>Menu</th>
                    @foreach ($accessRoles as $role)
                        <th class="text-center">{{ $role['role_code'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php($currentGroup = '__none__')
                @foreach ($accessMenus as $menu)
                    @if (($menu['group_name'] ?? null) !== $currentGroup)
                        @php($currentGroup = $menu['group_name'] ?? null)
                        @if ($currentGroup)
                            <tr class="table-group">
                                <td colspan="{{ count($accessRoles) + 1 }}" class="fw-semibold small text-body-secondary">
                                    {{ $currentGroup }}
                                </td>
                            </tr>
                        @endif
                    @endif

                    <tr data-menu="{{ $menu['id'] }}">
                        <td>
                            {{ $menu['menu_name'] }}
                            @if ($menu['is_locked'])
                                {{-- Flagged in the UI as well as enforced server-side, so the
                                     disabled ADMIN box reads as deliberate rather than broken. --}}
                                <i class="bi bi-lock-fill text-body-secondary ms-1"
                                   title="Menu terkunci — ADMIN selalu punya akses"></i>
                            @endif
                            <div class="small text-body-secondary">{{ $menu['menu_code'] }}</div>
                        </td>

                        @foreach ($accessRoles as $role)
                            @php($locked = $menu['is_locked'] && $role['role_code'] === \App\Models\Role::ADMIN)
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input js-menu-access"
                                       data-menu="{{ $menu['id'] }}" data-role="{{ $role['id'] }}"
                                       @checked(in_array($role['id'], $menu['role_ids'], true))
                                       @disabled($locked)>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

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
    window.HRIS_URLS = {
        base: @json(route('roles.index')),
        menuAccess: @json(route('roles.menu-access.update')),
    };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/roles.js') }}"></script>
<script src="{{ asset('js/menu-access.js') }}"></script>
@endpush
