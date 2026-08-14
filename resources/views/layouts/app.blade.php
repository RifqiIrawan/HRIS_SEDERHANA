<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    {{-- Spec §55: every AJAX call reads its CSRF token from here. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    {{-- Applied before the first paint so a dark-mode reload never flashes
         white. Inline for that reason: an external file would arrive too late. --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('hris-theme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-bs-theme', saved);
                }
            } catch (e) { /* private mode — the default theme is fine */ }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    @include('layouts.sidebar')

    <div class="app-main">
        @include('layouts.navbar')

        <main class="app-content">
            <div class="container-fluid px-3 px-lg-4 py-3 py-lg-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="h4 mb-0">@yield('page-title', View::getSection('title', 'Dashboard'))</h1>
                        @hasSection('page-subtitle')
                            <p class="text-body-secondary small mb-0 mt-1">@yield('page-subtitle')</p>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2">@yield('page-actions')</div>
                </div>

                @yield('content')
            </div>
        </main>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="hrisToasts" style="z-index:1090"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
