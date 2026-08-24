@extends('layouts.app')

@section('title', 'Profil')
@section('page-subtitle', 'Informasi akun dan ganti password')

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-body border-bottom fw-semibold">
                <i class="bi bi-person-badge me-1"></i>Informasi Akun
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 fw-normal text-body-secondary">Nama</dt>
                    <dd class="col-7 mb-2">{{ $user->name }}</dd>

                    <dt class="col-5 fw-normal text-body-secondary">Email</dt>
                    <dd class="col-7 mb-2">{{ $user->email }}</dd>

                    <dt class="col-5 fw-normal text-body-secondary">Role</dt>
                    <dd class="col-7 mb-2"><span class="badge text-bg-primary">{{ $user->roleCode() }}</span></dd>

                    <dt class="col-5 fw-normal text-body-secondary">Status</dt>
                    <dd class="col-7 mb-2"><span class="badge text-bg-success">{{ $user->status }}</span></dd>

                    <dt class="col-5 fw-normal text-body-secondary">Login Terakhir</dt>
                    <dd class="col-7 mb-0">{{ $user->last_login_at?->translatedFormat('d F Y H:i') ?? '−' }}</dd>
                </dl>

                @if ($user->employee)
                    <hr>
                    <div class="stat-label mb-2">Data Karyawan</div>
                    <dl class="row mb-0 small">
                        <dt class="col-5 fw-normal text-body-secondary">Kode</dt>
                        <dd class="col-7 mb-2">{{ $user->employee->employee_code }}</dd>

                        <dt class="col-5 fw-normal text-body-secondary">Nama Lengkap</dt>
                        <dd class="col-7 mb-2">{{ $user->employee->full_name }}</dd>

                        <dt class="col-5 fw-normal text-body-secondary">Tipe</dt>
                        <dd class="col-7 mb-2">{{ $user->employee->employment_type }}</dd>

                        <dt class="col-5 fw-normal text-body-secondary">Upah Harian</dt>
                        <dd class="col-7 mb-0 text-tabular">
                            Rp {{ number_format((float) $user->employee->daily_rate, 0, ',', '.') }}
                        </dd>
                    </dl>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-body border-bottom fw-semibold">
                <i class="bi bi-key me-1"></i>Ganti Password
            </div>
            <div class="card-body">
                <form id="passwordForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password"
                               placeholder="Password yang dipakai sekarang" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 8 karakter"
                               autocomplete="new-password" minlength="8" required>
                        <div class="form-text">Minimal 8 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                               placeholder="Ulangi password baru" autocomplete="new-password" minlength="8" required>
                    </div>

                    <button type="submit" class="btn btn-primary js-save-password">
                        <i class="bi bi-check-lg me-1"></i>Simpan Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    jQuery(function ($) {
        var $form = $('#passwordForm');

        $form.on('submit', function (event) {
            event.preventDefault();

            var $button = $('.js-save-password');
            HRIS.clearErrors($form);
            HRIS.busy($button, true, 'Menyimpan…');

            HRIS.api({
                url: @json(route('profile.password')),
                type: 'POST',
                data: $form.serialize() + '&_method=PUT'
            })
                .done(function () {
                    HRIS.toast('Password berhasil diubah.');
                    $form[0].reset();
                })
                .fail(function (error) {
                    HRIS.showErrors($form, error.errors);
                    HRIS.toast(error.message, 'danger');
                })
                .always(function () { HRIS.busy($button, false); });
        });
    });
</script>
@endpush
