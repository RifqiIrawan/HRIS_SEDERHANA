@extends('layouts.app')

@section('title', 'Assignment')
@section('page-subtitle', 'Penugasan karyawan ke lokasi dan shift')

@section('page-actions')
    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#rotationModal">
        <i class="bi bi-arrow-repeat me-1"></i>Generate Shift Harian
    </button>
    <button class="btn btn-primary js-create">
        <i class="bi bi-plus-lg me-1"></i>Tambah Assignment
    </button>
@endsection

@section('content')
<x-data-table :columns="7">
    <x-slot:filters>
        <div class="col-12 col-md-4">
            <input type="search" class="form-control" id="searchInput" name="search"
                   placeholder="Cari kode atau nama karyawan…">
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select" id="locationFilter" name="location_id">
                <option value="">Semua Lokasi</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" id="shiftFilter" name="shift_id">
                <option value="">Semua Shift</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" id="statusFilter" name="status">
                <option value="">Semua Status</option>
                <option value="ACTIVE">ACTIVE</option>
                <option value="INACTIVE">INACTIVE</option>
            </select>
        </div>
    </x-slot:filters>

    <x-slot:head>
        <th>Karyawan</th>
        <th>Lokasi</th>
        <th>Shift Awal</th>
        <th class="text-center">Mulai</th>
        <th class="text-center">Selesai</th>
        <th class="text-center">Status</th>
        <th class="text-end">Aksi</th>
    </x-slot:head>
</x-data-table>

<x-modal-form>
    <div class="col-12">
        <label class="form-label" for="employee_id">Karyawan <span class="text-danger">*</span></label>
        <select class="form-select" id="employee_id" name="employee_id" required></select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="location_id">Lokasi <span class="text-danger">*</span></label>
        <select class="form-select" id="location_id" name="location_id" required></select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="shift_id">Shift <span class="text-danger">*</span></label>
        <select class="form-select" id="shift_id" name="shift_id" required></select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="start_date">Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="start_date" name="start_date" required>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="end_date">Tanggal Selesai</label>
        <input type="date" class="form-control" id="end_date" name="end_date">
        <div class="form-text">Kosongkan bila penugasan berlaku seterusnya.</div>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="ACTIVE">ACTIVE</option>
            <option value="INACTIVE">INACTIVE</option>
        </select>
    </div>
</x-modal-form>

{{-- ── Generate rotasi shift ──────────────────────────────────────────
     Satu tombol yang menurunkan shift tiap siklus (mis. S3 → S2 → S1 → S3),
     menulis assignment per siklus sekaligus record shift harian per karyawan
     di Shift Roster — termasuk hari liburnya, yang sengaja dibuat jatuh pada
     tanggal berbeda antar karyawan supaya pos tetap terjaga. --}}
<div class="modal fade" id="rotationModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="rotationForm" novalidate>
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Generate Shift Harian (Rotasi)</h5>
                        <div class="small text-body-secondary">
                            Shift turun satu tingkat setiap siklus dan berputar kembali di akhir.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="rot_location_id">Lokasi <span class="text-danger">*</span></label>
                            <select class="form-select" id="rot_location_id" name="location_id" required></select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="rot_start_date">Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="rot_start_date" name="start_date" required>
                            <div class="form-text">Sebaiknya hari Senin agar siklus rapi per minggu.</div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="rot_end_date">Sampai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="rot_end_date" name="end_date" required>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="rot_cycle_days">Ganti shift setiap</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="rot_cycle_days" name="cycle_days"
                                       value="7" min="1" max="31" required>
                                <span class="input-group-text">hari</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="rot_direction">Arah rotasi</label>
                            <select class="form-select" id="rot_direction" name="direction">
                                <option value="DOWN">Turun (3 → 2 → 1)</option>
                                <option value="UP">Naik (1 → 2 → 3)</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="rot_off_days">Libur per siklus</label>
                            <select class="form-select" id="rot_off_days" name="off_days_per_cycle">
                                <option value="0">Tanpa libur</option>
                                <option value="1" selected>1 hari</option>
                                <option value="2">2 hari</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="rot_off_mode">Penentuan hari libur</label>
                            <select class="form-select" id="rot_off_mode" name="off_day_mode">
                                <option value="AUTO">Bergilir (beda tiap karyawan)</option>
                                <option value="FIXED">Hari tetap (sama semua)</option>
                            </select>
                        </div>

                        <div class="col-12 d-none" id="rot_off_weekdays_wrap">
                            <label class="form-label">Hari libur tetap</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'] as $iso => $label)
                                    <div class="form-check">
                                        <input class="form-check-input js-off-weekday" type="checkbox"
                                               name="off_weekdays[]" value="{{ $iso }}" id="off_wd_{{ $iso }}">
                                        <label class="form-check-label small" for="off_wd_{{ $iso }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">
                                Semua karyawan libur di hari yang sama — pos akan kosong pada hari itu.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center mb-1">
                                <span>Shift yang ikut rotasi <span class="text-danger">*</span></span>
                                <span>
                                    <button type="button" class="btn btn-sm btn-link p-0 me-2" id="rotShiftAll">Pilih semua</button>
                                    <button type="button" class="btn btn-sm btn-link p-0" id="rotShiftNone">Kosongkan</button>
                                </span>
                            </label>
                            <div class="d-flex flex-wrap gap-3" id="rotShiftList">
                                <span class="text-body-secondary small">Memuat shift…</span>
                            </div>
                            <div class="form-text">
                                Urutannya mengikuti jam mulai, dari paling pagi ke paling malam. Hilangkan
                                centang pada shift yang tidak dipakai — misalnya saat memakai pola 2 shift
                                12 jam sementara shift 8 jam lama masih berstatus ACTIVE. Minimal 2 shift.
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="rot_start_shift_id">Shift awal</label>
                            <select class="form-select" id="rot_start_shift_id" name="start_shift_id">
                                <option value="">Bagi rata otomatis</option>
                            </select>
                            <div class="form-text">
                                Hanya dipakai untuk karyawan yang belum punya assignment aktif. Yang sudah
                                punya selalu melanjutkan rotasi dari shift terakhirnya.
                            </div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="form-check mt-md-4">
                                <input class="form-check-input" type="checkbox" id="rot_replace" name="replace" value="1" checked>
                                <label class="form-check-label" for="rot_replace">
                                    Sesuaikan assignment lama yang bentrok
                                    <span class="text-body-secondary small d-block">
                                        Yang mulai sebelum rentang dipotong sampai H-1; yang mulai di dalam rentang dihapus.
                                    </span>
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="rot_with_roster" name="with_roster" value="1" checked>
                                <label class="form-check-label" for="rot_with_roster">
                                    Buat record shift harian per karyawan
                                    <span class="text-body-secondary small d-block">
                                        Mengisi Shift Roster harian — wajib agar karyawan bisa absen.
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Karyawan <span class="text-danger">*</span></span>
                                <span>
                                    <button type="button" class="btn btn-sm btn-link p-0 me-2" id="rotSelectAll">Pilih semua</button>
                                    <button type="button" class="btn btn-sm btn-link p-0" id="rotClearAll">Kosongkan</button>
                                </span>
                            </label>
                            <input type="search" class="form-control form-control-sm mb-2" id="rotEmployeeSearch"
                                   placeholder="Saring daftar karyawan…">
                            <div class="border rounded p-2" id="rotEmployeeList" style="max-height:200px; overflow-y:auto">
                                <div class="text-body-secondary small p-2">Memuat karyawan…</div>
                            </div>
                            <div class="form-text"><span id="rotSelectedCount">0</span> karyawan dipilih.</div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Pratinjau rotasi per karyawan</label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="rotRefreshPreview">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Perbarui pratinjau
                                </button>
                            </div>
                            <div class="border rounded" id="rotationPreview" style="max-height:320px; overflow:auto">
                                <div class="text-body-secondary small p-3">
                                    Pilih karyawan dan rentang tanggal, lalu perbarui pratinjau.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success js-rotate">
                        <i class="bi bi-arrow-repeat me-1"></i>Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Rekap hasil generate: record shift per karyawan, dengan hari liburnya. --}}
<div class="modal fade" id="rotationResultModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Hasil Generate — Record Shift per Karyawan</h5>
                    <div class="small text-body-secondary" id="rotationResultSummary"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="rotationResultBody"></div>
            <div class="modal-footer">
                <a href="{{ route('rosters.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-calendar-week me-1"></i>Lihat di Shift Roster
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Detail shift per karyawan ──────────────────────────────────────
     Dibuka dari tombol "Detail Shift" di tabel. Dua lapis sengaja
     ditampilkan berdampingan: periode assignment (kontraknya) dan jadwal
     harian (yang benar-benar berlaku per tanggal, termasuk hari libur dan
     koreksi manual dari layar Shift Roster). --}}
<div class="modal fade" id="shiftDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="shiftDetailName">Detail Shift</h5>
                    <div class="small text-body-secondary" id="shiftDetailMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1" for="detail_start_date">Dari Tanggal</label>
                        <input type="date" class="form-control form-control-sm" id="detail_start_date">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1" for="detail_end_date">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" id="detail_end_date">
                    </div>
                    <div class="col-12 col-md-6 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="detailPrevMonth">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="detailNextMonth">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                        <span class="ms-auto d-flex flex-wrap gap-2 align-items-center" id="shiftDetailSummary"></span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <div class="fw-semibold small mb-2">
                            <i class="bi bi-diagram-3 me-1"></i>Periode Assignment
                        </div>
                        <div class="border rounded" id="shiftDetailAssignments"></div>
                    </div>
                    <div class="col-12 col-lg-7">
                        <div class="fw-semibold small mb-2">
                            <i class="bi bi-calendar-week me-1"></i>Jadwal Harian
                        </div>
                        <div class="border rounded" style="max-height:420px; overflow:auto" id="shiftDetailRosters"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = {
        base: @json(route('assignments.index')),
        lookups: @json(route('lookups')),
        rotation: @json(route('assignments.rotation.generate')),
        rotationPreview: @json(route('assignments.rotation.preview')),
        employeeShifts: @json(url('assignments/employee'))
    };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/assignments.js') }}"></script>
<script src="{{ asset('js/assignment-rotation.js') }}"></script>
<script src="{{ asset('js/assignment-shift-detail.js') }}"></script>
@endpush
