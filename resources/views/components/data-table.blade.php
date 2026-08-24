{{--
    Card + toolbar + server-side DataTable.

    The card carries the whole module: a titled header band, a toolbar row
    holding the page-size menu and this page's filters, the grid, and a footer
    with the row count and the pager. Only the header band and the filter slot
    are rendered here — DataTables builds the rest around the <table>, which is
    why the <tbody> starts empty and nothing here paints pagination.

    The title band replaces the page heading the layout would otherwise print
    above the card; see the section claim below.

    Usage:
        <x-data-table>
            <x-slot:filters> … inputs … </x-slot:filters>
            <x-slot:head> <th>…</th> </x-slot:head>
        </x-data-table>
--}}
@props([
    'id' => 'dataTable',
    'filters' => null,
    // Defaults to the page's own title; pass one to override.
    'title' => null,
    // Accepted for callers that still pass it; DataTables derives the column
    // count from <thead>, so it is no longer needed for empty-row colspans.
    'columns' => null,
])

@php
    // Every page that uses this component sets these before its content
    // section, so they are already registered by the time the component runs.
    $cardTitle = $title ?? trim(strip_tags(View::getSection('title', '')));
    $cardSubtitle = trim(strip_tags(View::getSection('page-subtitle', '')));
    $cardActions = trim(View::getSection('page-actions', ''));
@endphp

{{--
    Tells layouts.app to stand down: the heading now lives in the card's own
    band, and two of them stacked would just be the same words twice. The
    layout renders content before it renders itself, so this claim is already
    in place by the time it checks. If it ever were not, the failure is a
    duplicated heading rather than a broken page.
--}}
@section('page-heading-in-card')@endsection

<div class="card overflow-hidden dt-card">
    <div class="dt-card-header">
        <div class="dt-card-title">
            <h2>{{ $cardTitle }}</h2>
            @if ($cardSubtitle)
                <p>{{ $cardSubtitle }}</p>
            @endif
        </div>

        @if ($cardActions)
            <div class="dt-card-actions">{!! $cardActions !!}</div>
        @endif
    </div>

    @if ($filters)
        {{-- Moved into the toolbar row by HRIS.dataTable, so that it shares a
             line with the page-size menu instead of forming a band of its own.
             Hidden until then, or it would flash in place first. --}}
        <div class="dt-filters" hidden>
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
