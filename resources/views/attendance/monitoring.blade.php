@extends('layouts.app')

@section('title', 'Monitoring Absensi')
@section('page-subtitle', 'Pantau kehadiran harian seluruh lokasi')

@section('page-actions')
    <button class="btn btn-outline-secondary" id="todayShortcut">
        <i class="bi bi-calendar-day me-1"></i>Hari Ini
    </button>
@endsection

@section('content')
<div class="row g-3 mb-3">
    @foreach ([
        ['label' => 'Hadir', 'key' => 'present', 'icon' => 'check-circle-fill', 'tone' => 'success'],
        ['label' => 'Terlambat', 'key' => 'late', 'icon' => 'clock-fill', 'tone' => 'warning'],
        ['label' => 'Belum Check-Out', 'key' => 'incomplete', 'icon' => 'hourglass-split', 'tone' => 'secondary'],
        ['label' => 'Belum Absen', 'key' => 'not_checked_in', 'icon' => 'exclamation-circle-fill', 'tone' => 'danger'],
    ] as $tile)
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="stat-icon text-bg-{{ $tile['tone'] }} bg-opacity-10 text-{{ $tile['tone'] }}">
                        <i class="bi bi-{{ $tile['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="stat-value" data-summary="{{ $tile['key'] }}">0</div>
                        <div class="stat-label">{{ $tile['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<x-data-table :columns="10">
    <x-slot:filters>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="startDate">Dari</label>
            <input type="date" class="form-control" id="startDate" name="start_date" value="{{ now()->toDateString() }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="endDate">Sampai</label>
            <input type="date" class="form-control" id="endDate" name="end_date" value="{{ now()->toDateString() }}">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small mb-1" for="searchInput">Karyawan</label>
            <input type="search" class="form-control" id="searchInput" name="search" placeholder="Kode atau nama…">
        </div>
        <div class="col-4 col-md-2">
            <label class="form-label small mb-1" for="locationFilter">Lokasi</label>
            <select class="form-select" id="locationFilter" name="location_id"><option value="">Semua</option></select>
        </div>
        <div class="col-4 col-md-1">
            <label class="form-label small mb-1" for="shiftFilter">Shift</label>
            <select class="form-select" id="shiftFilter" name="shift_id"><option value="">Semua</option></select>
        </div>
        <div class="col-4 col-md-2">
            <label class="form-label small mb-1" for="statusFilter">Status</label>
            <select class="form-select" id="statusFilter" name="status">
                <option value="">Semua</option>
                <option value="PRESENT">PRESENT</option>
                <option value="LATE">LATE</option>
                <option value="INCOMPLETE">INCOMPLETE</option>
                <option value="ABSENT">ABSENT</option>
            </select>
        </div>
    </x-slot:filters>

    <x-slot:head>
        <th>Tanggal</th>
        <th>Karyawan</th>
        <th>Shift</th>
        <th>Lokasi</th>
        <th class="text-center">Masuk</th>
        <th class="text-center">Keluar</th>
        <th class="text-end">Jarak</th>
        <th class="text-end">Akurasi</th>
        <th class="text-center">Status</th>
        <th class="text-center">Foto</th>
    </x-slot:head>
</x-data-table>

@include('attendance.partials.photo-modal')
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = {
        base: @json(route('attendance.monitoring')),
        lookups: @json(route('lookups'))
    };
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/attendance-list.js') }}"></script>
<script>
    jQuery(function ($) {
        var list = HRIS.attendanceList({ mode: 'monitoring', showEmployee: true });

        $('#todayShortcut').on('click', function () {
            var today = new Date().toISOString().slice(0, 10);
            $('#startDate').val(today);
            $('#endDate').val(today);
            list.reload(1);
        });
    });
</script>
@endpush
