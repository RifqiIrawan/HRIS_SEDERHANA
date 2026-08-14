@php($user = auth()->user())

<nav class="app-navbar navbar bg-body border-bottom sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        <button class="btn btn-outline-secondary d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-label="Buka menu">
            <i class="bi bi-list"></i>
        </button>

        <span class="navbar-text d-none d-lg-block text-body-secondary small">
            <i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('l, d F Y') }}
        </span>

        <button class="btn btn-outline-secondary ms-auto me-2 js-theme-toggle" type="button"
                aria-label="Ganti tema terang/gelap" title="Ganti tema">
            <i class="bi bi-moon-stars theme-icon"></i>
        </button>

        <div class="dropdown">
            <button class="btn btn-outline-secondary d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <span class="avatar-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <span class="text-start lh-sm d-none d-sm-block">
                    <span class="d-block small fw-semibold">{{ $user->name }}</span>
                    <span class="d-block text-body-secondary" style="font-size:.72rem">{{ $user->roleCode() }}</span>
                </span>
                <i class="bi bi-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
