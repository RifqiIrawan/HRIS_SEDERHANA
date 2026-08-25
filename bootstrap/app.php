<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\DataTableRequest;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\MenuAccessMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * Behind a TLS-terminating proxy — Tailscale serve, nginx, Cloudflare —
         * the request reaching PHP is plain HTTP, and without this Laravel
         * builds every asset() and route() URL as http:// while the browser is
         * on https://. The browser then blocks its own stylesheets and scripts
         * as mixed content and renders the raw HTML, which reads as the app
         * being broken rather than as a proxy header being ignored.
         *
         * Scoped to loopback rather than '*': such a proxy always forwards from
         * the same machine, and `artisan serve` binds 0.0.0.0 — so trusting
         * every client would let anyone on the LAN spoof X-Forwarded-* and
         * dictate the host Laravel signs its URLs with.
         */
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        // Runs before every web request so the DataTables vocabulary is already
        // translated by the time a controller reads the query string.
        $middleware->web(append: [
            DataTableRequest::class,
        ]);

        // Mobile clients reach the same controllers over the API stack, which
        // must never answer with HTML; see ForceJsonResponse.
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
            'menu' => MenuAccessMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Every module talks to the server over jQuery AJAX (spec §30), so an
        // error must always come back as JSON with a "message" the frontend can
        // surface — never as an HTML error page rendered into a table cell.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->ajax() && ! $request->wantsJson()) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first(),
                    'errors' => $e->errors(),
                    // Honour the status the exception carries: the login
                    // throttle raises a ValidationException with 429, and
                    // flattening it to 422 would hide the rate limit.
                ], $e->status);
            }

            if ($e instanceof BusinessRuleException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => $e->errorCode,
                    'data' => $e->payload ?: null,
                ], $e->getStatusCode());
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                ], 401);
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return response()->json([
                'success' => false,
                'message' => $status === 500 && ! config('app.debug')
                    ? 'Terjadi kesalahan pada server.'
                    : $e->getMessage(),
            ], $status);
        });
    })->create();
