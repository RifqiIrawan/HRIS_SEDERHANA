@extends('layouts.app')

@section('title', 'Riwayat Absensi')
@section('page-subtitle', $isHr ? 'Riwayat absensi seluruh karyawan' : 'Riwayat absensi Anda')

@section('content')
<x-data-table :columns="9">
    <x-slot:filters>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="startDate">Dari</label>
            <input type="date" class="form-control" id="startDate" name="start_date"
                   value="{{ now()->startOfMonth()->toDateString() }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1" for="endDate">Sampai</label>
            <input type="date" class="form-control" id="endDate" name="end_date"
                   value="{{ now()->endOfMonth()->toDateString() }}">
        </div>
        @if ($isHr)
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1" for="searchInput">Karyawan</label>
                <input type="search" class="form-control" id="searchInput" name="search" placeholder="Kode atau nama…">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="locationFilter">Lokasi</label>
                <select class="form-select" id="locationFilter" name="location_id">
                    <option value="">Semua</option>
                </select>
            </div>
        @endif
        <div class="col-6 col-md-2">
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
        @if ($isHr)<th>Karyawan</th>@endif
        <th>Shift</th>
        <th>Lokasi</th>
        <th class="text-center">Masuk</th>
        <th class="text-center">Keluar</th>
        <th class="text-end">Jarak</th>
        <th class="text-end">Akurasi</th>
        <th class="text-center">Status</th>
        <th class="text-center">Detail</th>
    </x-slot:head>
</x-data-table>

@include('attendance.partials.photo-modal')
@endsection

@push('scripts')
<script>
    window.HRIS_URLS = {
        base: @json(route('attendance.history')),
        // Collection URL for the detail modal; the row id is appended to it.
        detail: @json(url('attendance')),
        lookups: @json(route('lookups'))
    };
    window.HRIS_IS_HR = @json($isHr);
</script>
<script src="{{ asset('js/crud.js') }}"></script>
<script src="{{ asset('js/attendance-list.js') }}"></script>
<script>
    HRIS.attendanceList({ mode: 'history', showEmployee: window.HRIS_IS_HR });
</script>
@endpush
