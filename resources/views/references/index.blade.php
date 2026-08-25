{{--
    The one CRUD screen behind all three Karyawan reference masters.

    Each master has its own table, URL and menu; only the wording differs, and
    that arrives as $ref from ReferenceController::wording(). Three copies of
    this file would only be three places to fix the same bug.
--}}
@extends('layouts.app')

@section('title', $ref['title'])
@section('page-subtitle', $ref['subtitle'])

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah {{ $ref['title'] }}
    </button>
@endsection

@section('content')
<div class="alert alert-light border small">
    <i class="bi bi-info-circle me-1"></i>{!! $ref['note'] !!}
</div>

<x-data-table :columns="6">
    <x-slot:filters>
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari kode atau nama…">
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
        <th>Nama</th>
        <th>Keterangan</th>
        <th class="text-center">Urutan</th>
        <th class="text-center">Dipakai</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form>
    <div class="col-12 col-md-5">
        <label class="form-label" for="code">Kode <span class="text-danger">*</span></label>
        {{-- readonly rather than disabled for a system row: a disabled field is
             not serialised, and the server needs the code back to compare. --}}
        <input type="text" class="form-control text-uppercase" id="code" name="code" placeholder="KONTRAK"
               maxlength="30" required>
        <div class="form-text js-code-hint">Huruf kapital, angka dan garis bawah, mis. <code>KONTRAK</code>.</div>
    </div>
    <div class="col-12 col-md-7">
        <label class="form-label" for="name">Nama <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" placeholder="Kontrak" maxlength="100" required>
        <div class="form-text">Teks yang muncul di dropdown form Karyawan.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Keterangan</label>
        <textarea class="form-control" id="description" name="description"
                  placeholder="Penjelasan singkat, boleh dikosongkan" rows="2" maxlength="255"></textarea>
    </div>
    <div class="col-6 col-md-4">
        <label class="form-label" for="sort_order">Urutan</label>
        <input type="number" class="form-control" id="sort_order" name="sort_order" placeholder="10" min="0" max="9999">
        <div class="form-text">Makin kecil makin atas.</div>
    </div>
    <div class="col-6 col-md-4">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="ACTIVE">ACTIVE</option>
            <option value="INACTIVE">INACTIVE</option>
        </select>
        <div class="form-text">INACTIVE tetap tersimpan, hanya hilang dari dropdown.</div>
    </div>
    <div class="col-12">
        <div class="alert alert-warning small mb-0 d-none js-system-note">
            <i class="bi bi-shield-lock me-1"></i>Baris sistem: kode dan status dikunci karena dipakai
            langsung oleh logika aplikasi. Nama dan keterangan tetap bisa diubah.
        </div>
    </div>
</x-modal-form>
@endsection

@push('scripts')
<script>
    window.PARKOPS_URLS = { base: @json($ref['base']) };
    window.PARKOPS_REF = @json(['title' => $ref['title'], 'entity' => $ref['entity']]);
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/reference.js') }}"></script>
@endpush
