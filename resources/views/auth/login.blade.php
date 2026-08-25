<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/parkops-icon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="any">

    {{-- Matches the app shell: the theme is settled before the first paint. --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('parkops-theme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-bs-theme', saved);
                }
            } catch (e) { /* private mode — the default theme is fine */ }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            /* Soft neutral: the ground stays quiet and the card carries the eye,
               so the login screen reads as the same product as the app. */
            background: var(--surface-sunken);
            padding: 1.5rem;
        }

        .login-card { width: 100%; max-width: 396px; }

        .login-card h1 {
            margin: 0;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        {{-- The tagline rides inside the lockup, so it is not repeated here. --}}
        <h1 class="mb-0"><x-brand size="lg" :tagline="true" /></h1>
    </div>

    @php
        // Dev convenience: the seeder's ADMIN account is pre-filled so the form can be
        // submitted straight away. Guarded to `local` — never pre-fill in production.
        $demoEmail = app()->environment('local') ? 'admin@parkops.test' : '';
        $demoPassword = app()->environment('local') ? 'password' : '';
    @endphp

    <div class="card">
        <div class="card-body p-4">
            <form id="loginForm" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="nama@perusahaan.com"
                               value="{{ $demoEmail }}"
                               autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password akun Anda"
                               value="{{ $demoPassword }}"
                               autocomplete="current-password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                    <label class="form-check-label small" for="remember">Ingat saya</label>
                </div>

                <div class="alert alert-danger d-none py-2 small" id="loginAlert" role="alert"></div>

                <button type="submit" class="btn btn-primary w-100" id="loginButton">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-body-secondary small mt-3 mb-0">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </p>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
    window.PARKOPS_ROUTES = { login: @json(route('login.attempt')) };
</script>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
