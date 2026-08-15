@extends('layouts.app')

@section('title', 'Absensi')
@section('page-subtitle', $employee->employee_code . ' — ' . $employee->full_name)

@push('styles')
    {{-- Leaflet 1.9.4 dilayani dari public/vendor/leaflet, bukan CDN: halaman
         absensi harus tetap bisa dipakai saat internet lokasi sedang buruk. --}}
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
@endpush

@section('content')
{{--
    One page serves both check-in and check-out: the server decides which
    action is currently available (spec §27, §28) and the UI follows its
    `state`, so the employee can never submit the wrong one.
--}}
<div class="row g-3 justify-content-center">
    <div class="col-12 col-xl-9">

        <div class="card mb-3" id="rosterCard">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <div class="stat-label mb-1">Jadwal Aktif</div>
                        <div class="fw-semibold" id="rosterTitle">Memuat…</div>
                        <div class="small text-body-secondary" id="rosterSubtitle"></div>
                    </div>
                    <div class="text-end">
                        <div class="stat-label mb-1">Waktu Server</div>
                        <div class="fs-5 fw-semibold text-tabular" id="serverClock">--:--:--</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert d-none" id="stateAlert" role="alert"></div>

        {{-- Shown only while config('hris.enforce_geofence') is false, so the
             screen never quietly accepts a reading from anywhere. --}}
        <div class="alert alert-danger d-none" id="geofenceWarning" role="alert">
            <i class="bi bi-shield-exclamation me-1"></i>
            <strong>Mode uji coba:</strong> penegakan jarak &amp; akurasi GPS sedang dinonaktifkan.
            Absensi diterima dari posisi mana pun. Aktifkan kembali
            <code>HRIS_ENFORCE_GEOFENCE=true</code> sebelum digunakan sungguhan.
        </div>

        <div id="attendancePanel" class="d-none">
            <div class="row g-3">

                {{-- ── Map + GPS readout (spec §20) ──────────────────── --}}
                <div class="col-12 col-lg-7">
                    <div class="card h-100">
                        <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between">
                            <span class="fw-semibold"><i class="bi bi-geo-alt me-1"></i>Lokasi Anda</span>
                            <button class="btn btn-sm btn-outline-secondary" id="refreshGps">
                                <i class="bi bi-arrow-clockwise me-1"></i>Perbarui GPS
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="attendanceMap"></div>

                            <div class="gps-readout mt-3">
                                <div>
                                    <div class="readout-label">Akurasi GPS</div>
                                    <div class="readout-value" id="readoutAccuracy">—</div>
                                </div>
                                <div>
                                    <div class="readout-label">Jarak</div>
                                    <div class="readout-value" id="readoutDistance">—</div>
                                </div>
                                <div>
                                    <div class="readout-label">Status</div>
                                    <div class="readout-value" id="readoutStatus">—</div>
                                </div>
                            </div>

                            {{-- Reverse-geocoded label for the reading above. Descriptive
                                 only — the geofence verdict is computed from the
                                 coordinates, never from this text. --}}
                            <div class="gps-address mt-3">
                                <div class="readout-label">Alamat GPS</div>
                                <div class="gps-address-value" id="readoutAddress">—</div>
                            </div>

                            <div class="alert alert-light border small mt-3 mb-0" id="gpsHint">
                                <i class="bi bi-info-circle me-1"></i>
                                Menunggu sinyal GPS…
                            </div>

                            {{-- Spec §24: what is shown here is an estimate for the
                                 employee's benefit; Laravel recomputes the distance and
                                 makes the real decision on submit. --}}
                            <p class="text-body-secondary mt-2 mb-0" style="font-size:.72rem">
                                Jarak di layar hanya perkiraan. Keputusan valid/tidak dilakukan server saat absensi dikirim.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── Camera (spec §25, §26) ────────────────────────── --}}
                <div class="col-12 col-lg-5">
                    <div class="card h-100">
                        <div class="card-header bg-body border-bottom fw-semibold">
                            <i class="bi bi-camera me-1"></i>Foto Absensi
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="camera-stage mb-3">
                                <video id="cameraVideo" playsinline muted class="d-none"></video>
                                <img id="photoPreview" class="d-none" alt="Pratinjau foto absensi">
                                <div class="camera-placeholder" id="cameraPlaceholder">
                                    <i class="bi bi-camera" style="font-size:2rem"></i>
                                    <span>Kamera belum aktif</span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" id="openCamera">
                                    <i class="bi bi-camera-video me-1"></i>Aktifkan Kamera
                                </button>
                                <button class="btn btn-primary d-none" id="capturePhoto">
                                    <i class="bi bi-camera-fill me-1"></i>Ambil Foto
                                </button>
                                <button class="btn btn-outline-secondary d-none" id="retakePhoto">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Ambil Ulang
                                </button>

                                {{-- Spec §25 fallback for browsers that refuse getUserMedia
                                     (notably iOS Safari over plain HTTP). --}}
                                <label class="btn btn-outline-secondary mb-0" for="photoFallback">
                                    <i class="bi bi-upload me-1"></i>Ambil dari Kamera Perangkat
                                </label>
                                <input type="file" id="photoFallback" accept="image/*" capture="user" class="d-none">
                            </div>

                            <p class="text-body-secondary small mt-3 mb-0">
                                Foto wajib untuk check-in maupun check-out. Maksimal 5 MB (JPEG/PNG/WEBP).
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── Action ────────────────────────────────────────── --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <button class="btn btn-lg btn-success w-100 d-none" id="checkInButton" disabled>
                                <i class="bi bi-box-arrow-in-right me-1"></i>CHECK IN
                            </button>
                            <button class="btn btn-lg btn-warning w-100 d-none" id="checkOutButton" disabled>
                                <i class="bi bi-box-arrow-right me-1"></i>CHECK OUT
                            </button>
                            <div class="small text-body-secondary text-center mt-2" id="actionHint"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Result summary after a successful submit ──────────────── --}}
        <div class="card mt-3 d-none" id="resultCard">
            <div class="card-header bg-body border-bottom fw-semibold">
                <i class="bi bi-clipboard-check me-1"></i>Absensi Hari Ini
            </div>
            <div class="card-body">
                <dl class="row mb-0 small" id="resultBody"></dl>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    window.HRIS_URLS = {
        context: @json(route('attendance.index')),
        checkIn: @json(route('attendance.check-in')),
        checkOut: @json(route('attendance.check-out')),
        history: @json(route('attendance.history')),
        geocode: @json(route('attendance.geocode'))
    };
</script>
<script src="{{ asset('js/attendance.js') }}"></script>
@endpush
