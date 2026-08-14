{{--
    Card + filter bar + server-side DataTable.

    The <tbody> starts empty: DataTables fills it, and also renders the page
    length, the row count and the pager into its own wrapper around this table
    (see HRIS.dataTable in public/js/app.js). Nothing here paints pagination.

    Usage:
        <x-data-table>
            <x-slot:filters> … inputs … </x-slot:filters>
            <x-slot:head> <th>…</th> </x-slot:head>
        </x-data-table>
--}}
@props([
    'id' => 'dataTable',
    'filters' => null,
    // Accepted for callers that still pass it; DataTables derives the column
    // count from <thead>, so it is no longer needed for empty-row colspans.
    'columns' => null,
])

<div class="card overflow-hidden">
    @if ($filters)
        <div class="card-body border-bottom py-3 dt-filters">
            <div class="row g-2 align-items-center">{{ $filters }}</div>
        </div>
    @endif

    <table class="table table-hover align-middle mb-0" id="{{ $id }}" style="width:100%">
        <thead>
            <tr>{{ $head }}</tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
