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

<div class="card mt-4 menu-access" id="menuAccess">
    <div class="dt-card-header">
        <div class="dt-card-title">
            <h2><i class="bi bi-diagram-3 me-1"></i>Akses Menu</h2>
            <p>
                Centang menu yang boleh dibuka role terpilih. Pengaturan ini juga menutup URL-nya,
                bukan sekadar menyembunyikan menu.
            </p>
        </div>
        <div class="dt-card-actions">
            <button class="btn btn-primary" id="saveMenuAccess">
                <i class="bi bi-check2 me-1"></i>Simpan Akses
            </button>
        </div>
    </div>

    {{-- One role at a time: the screen keeps the same width whether there are
         three roles or fifty, which the old role-per-column matrix could not. --}}
    <div class="menu-access-toolbar">
        <label class="form-label mb-0" for="accessRole">Role</label>
        <select class="form-select" id="accessRole">
            @foreach ($accessRoles as $role)
                <option value="{{ $role['id'] }}" data-code="{{ $role['role_code'] }}">
                    {{ $role['role_code'] }} — {{ $role['role_name'] }}@unless ($role['status'] === \App\Models\Role::ACTIVE) (nonaktif)@endunless
                </option>
            @endforeach
        </select>
        <span class="menu-access-count" id="accessCount"></span>
    </div>

    <div class="menu-access-list">
        @php($currentGroup = '__none__')
        @foreach ($accessMenus as $menu)
            @if (($menu['group_name'] ?? null) !== $currentGroup)
                @php($currentGroup = $menu['group_name'] ?? null)
                <div class="menu-access-group" data-group="{{ $currentGroup ?: 'Umum' }}">
                    <span>{{ $currentGroup ?: 'Umum' }}</span>
                    <button type="button" class="btn btn-sm btn-link js-group-toggle"
                            data-group="{{ $currentGroup ?: 'Umum' }}">Pilih semua</button>
                </div>
            @endif

            <label class="menu-access-item" data-group="{{ $currentGroup ?: 'Umum' }}">
                <input type="checkbox" class="form-check-input js-menu-access"
                       data-menu="{{ $menu['id'] }}"
                       data-locked="{{ $menu['is_locked'] ? 1 : 0 }}">
                <span class="menu-access-label">
                    <span class="menu-access-name">
                        {{ $menu['menu_name'] }}
                        @if ($menu['is_locked'])
                            {{-- Flagged here as well as enforced server-side, so the box
                                 the screen refuses to clear reads as deliberate. --}}
                            <i class="bi bi-lock-fill text-body-secondary ms-1"
                               title="Menu terkunci — ADMIN selalu punya akses"></i>
                        @endif
                        @if ($menu['is_action'])
                            {{-- No sidebar entry to recognise it by, so the row says what it governs. --}}
                            <span class="badge text-bg-light border ms-1 fw-normal">aksi</span>
                        @endif
                    </span>
                    <code class="menu-access-code">{{ $menu['menu_code'] }}</code>
                </span>
            </label>
        @endforeach
    </div>
</div>

<x-modal-form size="modal-md">
    <div class="col-12">
        <label class="form-label" for="role_code">Kode Role <span class="text-danger">*</span></label>
        <input type="text" class="form-control text-uppercase" id="role_code" name="role_code"
               placeholder="SUPERVISOR" maxlength="30" required>
        <div class="form-text">Huruf kapital, angka, dan garis bawah saja.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="role_name">Nama Role <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="role_name" name="role_name"
               placeholder="Supervisor Lapangan" maxlength="100" required>
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

    // The current mapping, so switching role is instant instead of a fetch.
    window.HRIS_MENU_ACCESS = @json($accessMenus);

    // Only the signed-in user's own role changes what the sidebar should show,
    // so only that save has to reload the page.
    window.HRIS_MENU_ACCESS_SELF = @json(auth()->user()?->role_id);
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/roles.js') }}"></script>
<script src="{{ asset('js/menu-access.js') }}"></script>
@endpush
