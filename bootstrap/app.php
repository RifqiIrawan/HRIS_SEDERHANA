<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\DataTableRequest;
use App\Http\Middleware\EnsureUserIsActive;
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
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Runs before every web request so the DataTables vocabulary is already
        // translated by the time a controller reads the query string.
        $middleware->web(append: [
            DataTableRequest::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => EnsureUserIsActive::class,
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
