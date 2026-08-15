{{--
    Detail viewer for one attendance row: the check-in / check-out photos plus
    the readings that were recorded with them. Everything except the
    coordinates is already on the listing payload, so the modal paints
    immediately; the coordinates arrive from GET /attendance/{id}.

    Images load from the authenticated route, never from a public storage URL.
--}}
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Detail Absensi</h5>
                    <div class="text-body-secondary small" data-detail="date">−</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Row-level facts: who, which shift, where, and the verdict. --}}
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3" data-detail-block="employee">
                        <div class="stat-label">Karyawan</div>
                        <div class="small fw-semibold" data-detail="employee_name">−</div>
                        <div class="text-body-secondary" style="font-size:.72rem" data-detail="employee_code"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-label">Shift</div>
                        <div class="small fw-semibold" data-detail="shift_code">−</div>
                        <div class="text-body-secondary" style="font-size:.72rem" data-detail="shift_name"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-label">Lokasi</div>
                        <div class="small fw-semibold" data-detail="location_name">−</div>
                        <div class="text-body-secondary" style="font-size:.72rem" data-detail="radius"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-label">Status</div>
                        <div class="small" data-detail="status">−</div>
                        <div class="text-body-secondary" style="font-size:.72rem" data-detail="duration"></div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach (['in' => 'Check-In', 'out' => 'Check-Out'] as $side => $label)
                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-body border-bottom d-flex align-items-center justify-content-between py-2">
                                    <span class="fw-semibold small">
                                        <i class="bi bi-box-arrow-{{ $side === 'in' ? 'in-right' : 'right' }} me-1"></i>{{ $label }}
                                    </span>
                                    <span class="text-tabular fw-semibold small" data-detail="{{ $side }}.time">−</span>
                                </div>
                                <div class="card-body">
                                    <img class="img-fluid rounded border w-100 mb-3 d-none"
                                         data-detail="{{ $side }}.photo" alt="Foto {{ $label }}">
                                    <div class="text-body-secondary small mb-3 d-none"
                                         data-detail="{{ $side }}.photo_empty">Tidak ada foto.</div>

                                    <dl class="row row-cols-2 g-0 mb-0 small">
                                        <dt class="col-5 fw-normal text-body-secondary">Jarak</dt>
                                        <dd class="col-7 mb-1 text-tabular" data-detail="{{ $side }}.distance">−</dd>

                                        <dt class="col-5 fw-normal text-body-secondary">Akurasi GPS</dt>
                                        <dd class="col-7 mb-1 text-tabular" data-detail="{{ $side }}.accuracy">−</dd>

                                        <dt class="col-5 fw-normal text-body-secondary">Koordinat</dt>
                                        <dd class="col-7 mb-1 text-tabular" data-detail="{{ $side }}.coordinates">−</dd>

                                        <dt class="col-5 fw-normal text-body-secondary">Alamat</dt>
                                        <dd class="col-7 mb-0" data-detail="{{ $side }}.address">−</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Same caveat as the check-in screen: the address is a label for
                     the reading, the geofence verdict came from the coordinates. --}}
                <p class="text-body-secondary mt-3 mb-0" style="font-size:.72rem">
                    Jarak dihitung server saat absensi dikirim. Alamat hanya keterangan hasil reverse-geocoding.
                </p>
            </div>
        </div>
    </div>
</div>
