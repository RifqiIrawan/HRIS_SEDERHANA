{{--
    Viewer for the check-in / check-out photos. Images load from the
    authenticated route, never from a public storage URL.
--}}
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="stat-label mb-2">Check-In</div>
                        <img id="photoCheckIn" class="img-fluid rounded border w-100" alt="Foto check-in">
                        <div class="text-body-secondary small mt-2 d-none" id="photoCheckInEmpty">Tidak ada foto.</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="stat-label mb-2">Check-Out</div>
                        <img id="photoCheckOut" class="img-fluid rounded border w-100" alt="Foto check-out">
                        <div class="text-body-secondary small mt-2 d-none" id="photoCheckOutEmpty">Tidak ada foto.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
