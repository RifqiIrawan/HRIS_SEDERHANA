@extends('layouts.app')

@section('title', 'Lokasi')
@section('page-subtitle', 'Titik absensi, radius geofence, dan batas akurasi GPS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('page-actions')
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Lokasi
    </button>
@endsection

@section('content')
<x-data-table :columns="7">
    <x-slot:filters>
        <div class="col-12 col-md-5">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari kode, nama, atau alamat lokasi…">
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
        <th>Nama Lokasi</th>
        <th>Koordinat</th>
        <th class="text-center">Radius</th>
        <th class="text-center">Batas Akurasi</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form size="modal-xl">
    <div class="col-12 col-lg-5">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-lg-12">
                <label class="form-label" for="location_code">Kode Lokasi <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="location_code" name="location_code"
                       placeholder="LOC004" maxlength="30" required>
            </div>
            <div class="col-12 col-sm-6 col-lg-12">
                <label class="form-label" for="location_name">Nama Lokasi <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="location_name" name="location_name"
                       placeholder="Kantor Pusat" maxlength="150" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="address">Alamat</label>
                <textarea class="form-control" id="address" name="address"
                          placeholder="Jl. Sudirman No. 1, Jakarta Selatan" rows="2" maxlength="500"></textarea>
            </div>
            <div class="col-6">
                <label class="form-label" for="latitude">Latitude <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-tabular" id="latitude" name="latitude"
                       placeholder="Klik titik pada peta" required readonly>
            </div>
            <div class="col-6">
                <label class="form-label" for="longitude">Longitude <span class="text-danger">*</span></label>
                <input type="text" class="form-control text-tabular" id="longitude" name="longitude"
                       placeholder="Klik titik pada peta" required readonly>
            </div>
            <div class="col-6">
                <label class="form-label" for="radius_meter">Radius (m) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="radius_meter" name="radius_meter" placeholder="10"
                       min="1" max="10" step="1" required>
                <div class="form-text">Maksimal 10 meter (spec §23).</div>
            </div>
            <div class="col-6">
                <label class="form-label" for="gps_accuracy_limit">Batas Akurasi (m) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="gps_accuracy_limit" name="gps_accuracy_limit" placeholder="20"
                       min="1" max="100" step="1" required>
                <div class="form-text">Default 20 meter.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select class="form-select" id="status" name="status" required>
                    <option value="ACTIVE">ACTIVE</option>
                    <option value="INACTIVE">INACTIVE</option>
                </select>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <label class="form-label d-flex justify-content-between align-items-center">
            <span>Titik Lokasi <span class="text-danger">*</span></span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="useMyLocation">
                <i class="bi bi-crosshair me-1"></i>Pakai Lokasi Saya
            </button>
        </label>

        {{-- Spec §13 — klik peta atau geser marker untuk menentukan titik. --}}
        <div id="locationMap"></div>

        <div class="form-text mt-2">
            <i class="bi bi-info-circle me-1"></i>
            Klik pada peta atau geser marker merah untuk menentukan titik absensi.
            Lingkaran biru menunjukkan radius geofence.
        </div>
    </div>
</x-modal-form>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    window.HRIS_URLS = { base: @json(route('locations.index')) };
    window.HRIS_DEFAULTS = {
        radius: {{ $defaultRadius }},
        accuracy: {{ $defaultAccuracy }},
        // Monas, Jakarta — a sane starting view before any point is chosen.
        center: [-6.175392, 106.827153]
    };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/locations.js') }}"></script>
@endpush
