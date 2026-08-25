@extends('layouts.app')

@section('title', 'Role')
@section('page-subtitle', 'Peran akses dan menu yang boleh dibuka tiap peran')

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Role
    </button>
@endsection

@section('content')
{{-- Two cards, deliberately: the list answers "peran apa saja yang ada", the
     panel below answers "peran ini boleh apa". They are separate questions and
     an administrator is only ever asking one of them at a time. --}}
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

{{--
    Hak akses opens over the role it belongs to rather than standing below the
    table. Nineteen menus and their switches made a card taller than the screen,
    so the two controls that matter — which role, and Simpan — spent most of
    their life scrolled out of view. In a dialog the list is the only thing that
    scrolls and those two never move.
--}}
<div class="modal fade" id="accessModal" tabindex="-1" aria-hidden="true" aria-labelledby="accessModalTitle">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content menu-access" id="menuAccess">
            <div class="modal-header">
                <div class="access-modal-heading">
                    <h5 class="modal-title" id="accessModalTitle">
                        Hak Akses <span class="access-role-name" id="accessRoleLabel"></span>
                    </h5>
                    <span class="access-role-badges" id="accessRoleBadges"></span>
                    <p>
                        Setiap sakelar adalah satu izin nyata. Mematikan <em>delete</em> ikut
                        menutup URL-nya, bukan sekadar menyembunyikan tombolnya.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

    {{-- The role now comes from the row that opened the dialog, so this is no
         longer a control — but it stays as the element every part of
         menu-access.js reads the current role, its code and its system flag
         from. Hiding it keeps that one source of truth instead of scattering
         the same three facts across data attributes. --}}
    <div class="d-none">
        <div>
            <label class="form-label" for="accessRole">Role</label>
            <select class="form-select" id="accessRole">
                @foreach ($accessRoles as $role)
                    <option value="{{ $role['id'] }}" data-code="{{ $role['role_code'] }}"
                            data-system="{{ $role['is_system'] ? 1 : 0 }}">
                        {{ $role['role_code'] }} — {{ $role['role_name'] }}@unless ($role['status'] === \App\Models\Role::ACTIVE) (nonaktif)@endunless
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Copy and search stay outside .modal-body so they hold still while the
         list behind them scrolls. --}}
    <div class="access-controls">

        {{-- Copying loads the other role's switches into the form and stops
             there. Nothing is written until Simpan, so a wrong pick costs a
             second pick rather than an undo. --}}
        <div class="access-field access-field-grow">
            <label class="form-label" for="accessCopy">Copy dari role</label>
            <select class="form-select" id="accessCopy">
                <option value="">— pilih role —</option>
                @foreach ($accessRoles as $role)
                    <option value="{{ $role['id'] }}">{{ $role['role_code'] }} — {{ $role['role_name'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="access-field access-field-grow">
            <label class="form-label" for="accessSearch">Cari menu</label>
            <input type="search" class="form-control" id="accessSearch"
                   placeholder="Cari menu atau kode…" autocomplete="off">
        </div>
    </div>

    <div class="access-toolbar">
        <div class="access-toolbar-actions">
            <label class="access-check-all">
                <input type="checkbox" class="form-check-input" id="accessCheckAll">
                <span>Pilih semua</span>
            </label>
            <button type="button" class="btn btn-sm btn-icon btn-icon-label" id="accessExpand">
                <i class="bi bi-chevron-expand"></i>Buka semua
            </button>
            <button type="button" class="btn btn-sm btn-icon btn-icon-label" id="accessCollapse">
                <i class="bi bi-chevron-contract"></i>Tutup semua
            </button>
        </div>
    </div>

    <div class="modal-body p-0">
    <div class="access-list" id="accessList">
        {{-- A header row, because the right-hand column holds controls now
             rather than a label, and an unannounced bank of switches makes the
             reader work out what it is they are switching. --}}
        <div class="access-head">
            <span>Menu</span>
            <span>Actions</span>
        </div>

        @foreach ($accessGroups as $group)
            <section class="access-group" data-group="{{ $group['slug'] }}">
                <div class="access-group-head">
                    <button type="button" class="access-caret js-group-collapse"
                            aria-expanded="true" aria-controls="body-{{ $group['slug'] }}"
                            aria-label="Lipat grup {{ $group['name'] }}">
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <label class="access-group-label">
                        <input type="checkbox" class="form-check-input js-group-all"
                               data-group="{{ $group['slug'] }}">
                        <span>{{ $group['name'] }}</span>
                    </label>

                    <span class="access-group-count" data-count="{{ $group['slug'] }}"></span>
                </div>

                <div class="access-group-body" id="body-{{ $group['slug'] }}">
                    @foreach ($group['menus'] as $menu)
                        <div class="access-item" data-menu="{{ $menu['id'] }}" data-group="{{ $group['slug'] }}"
                             data-search="{{ Str::lower($menu['menu_name'].' '.$menu['menu_code']) }}">
                            <label class="access-item-main">
                                <input type="checkbox" class="form-check-input js-menu-access"
                                       data-menu="{{ $menu['id'] }}"
                                       data-group="{{ $group['slug'] }}"
                                       data-locked="{{ $menu['is_locked'] ? 1 : 0 }}">
                                <span class="access-item-text">
                                    <span class="access-item-name">
                                        {{ $menu['menu_name'] }}
                                        @if ($menu['is_locked'])
                                            {{-- Enforced server-side too; flagged here so the switches
                                                 the screen refuses to clear read as deliberate. --}}
                                            <i class="bi bi-lock-fill" title="Selalu terbuka untuk ADMIN"></i>
                                        @endif
                                        @if ($menu['is_action'])
                                            {{-- No sidebar entry to recognise it by, so the row says
                                                 that what it governs is an action, not a screen. --}}
                                            <span class="badge badge-soft">aksi</span>
                                        @endif
                                    </span>
                                    <code class="access-item-code">{{ $menu['menu_code'] }}</code>
                                </span>
                            </label>

                            {{-- One switch per verb the menu's routes actually answer. A
                                 read-only screen shows one switch rather than four, three
                                 of which would enforce nothing whichever way they sat. --}}
                            <span class="access-item-actions">
                                @forelse ($menu['available'] as $action)
                                    <label class="access-switch form-switch">
                                        <input class="form-check-input js-action" type="checkbox" role="switch"
                                               data-menu="{{ $menu['id'] }}" data-action="{{ $action }}"
                                               aria-label="{{ $action }} {{ $menu['menu_name'] }}">
                                        <span>{{ $action }}</span>
                                    </label>
                                @empty
                                    <span class="access-noroute">tanpa rute</span>
                                @endforelse
                            </span>

                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="access-empty d-none" id="accessEmpty">
            <span class="dt-empty-icon"><i class="bi bi-search"></i></span>
            <p class="dt-empty-title">Tidak ada menu yang cocok dengan pencarian.</p>
        </div>
    </div>
    </div>

            <div class="modal-footer">
                <span class="access-summary" id="accessCount"></span>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveMenuAccess">
                    <i class="bi bi-check2 me-1"></i>Simpan Akses
                </button>
            </div>
        </div>
    </div>
</div>

<x-modal-form size="modal-md">
    <div class="col-12 js-system-role-note d-none">
        <div class="alert alert-light border small mb-0">
            <i class="bi bi-shield-lock me-1"></i>
            Ini role bawaan sistem. Namanya boleh diubah, tapi kodenya dikunci dan
            rolenya tidak bisa dihapus karena logika otorisasi merujuk ke kode itu.
        </div>
    </div>
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
    window.PARKOPS_URLS = {
        base: @json(route('roles.index')),
        menuAccess: @json(route('roles.menu-access.update')),
    };

    // The current mapping, so switching role is instant instead of a fetch.
    window.PARKOPS_MENU_ACCESS = @json($accessGroups);

    // Only the signed-in user's own role changes what the sidebar should show,
    // so only that save has to reload the page.
    window.PARKOPS_MENU_ACCESS_SELF = @json(auth()->user()?->role_id);
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/roles.js') }}"></script>
<script src="{{ asset('js/menu-access.js') }}"></script>
@endpush
